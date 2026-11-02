<?php

declare(strict_types=1);

namespace App\Support\Inventory;

use App\Models\Inventory\Item;
use App\Models\Inventory\Stock;
use App\Models\Inventory\StockMovement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The only place stock changes. Every change writes a movement (ledger) row, then updates
 * the warehouse stock and the item's total stock. Services never hold stock.
 *
 *   Stock::move($item, $warehouseId, -2, 'sale', $invoice);
 */
class StockService
{
    /** @param  float  $qty  signed: + stock in, - stock out */
    public static function move(Item|int|string $item, int|string $warehouseId, float|int|string $qty, string $type, ?Model $ref = null, ?string $note = null, ?string $date = null, bool $allowNegative = true): void
    {
        $warehouseId = (int) $warehouseId;
        $qty = (float) $qty;
        $item = $item instanceof Item ? $item : Item::query()->withTrashed()->findOrFail((int) $item);
        if ($item->is_service || abs($qty) < 0.0000001) {
            return;
        }

        DB::transaction(function () use ($item, $warehouseId, $qty, $type, $ref, $note, $date, $allowNegative) {
            $stock = Stock::query()->lockForUpdate()->firstOrCreate(['warehouse_id' => $warehouseId, 'item_id' => $item->id], ['qty' => 0]);
            if (! $allowNegative && $qty < 0 && $stock->qty + $qty < -0.0001) {
                throw ValidationException::withMessages(['items' => "Not enough stock for {$item->name} (available: ".self::fmt($stock->qty).').']);
            }
            $stock->update(['qty' => $stock->qty + $qty]);

            StockMovement::create([
                'warehouse_id' => $warehouseId, 'item_id' => $item->id, 'type' => $type, 'qty' => $qty,
                'reference_type' => $ref?->getMorphClass(), 'reference_id' => $ref?->getKey(),
                'date' => $date ?? now()->toDateString(), 'note' => $note, 'created_by' => auth()->id(),
            ]);

            self::syncItemTotal($item->id);
        });
    }

    /** Reverse every movement written for a document (used before re-saving or deleting it). */
    public static function reverse(Model $ref): void
    {
        DB::transaction(function () use ($ref) {
            $moves = StockMovement::query()->where('reference_type', $ref->getMorphClass())->where('reference_id', $ref->getKey())->get();
            foreach ($moves as $m) {
                Stock::query()->where('warehouse_id', $m->warehouse_id)->where('item_id', $m->item_id)->decrement('qty', $m->qty);
            }
            StockMovement::query()->whereIn('id', $moves->pluck('id'))->delete();
            foreach ($moves->pluck('item_id')->unique() as $id) {
                self::syncItemTotal($id);
            }
        });
    }

    /** Item total = sum of its warehouses; a variant parent = sum of its children. */
    public static function syncItemTotal(int $itemId): void
    {
        $item = Item::query()->withTrashed()->find($itemId);
        if (! $item) {
            return;
        }
        if ($item->item_group === 'variant') {
            Item::query()->withTrashed()->whereKey($itemId)->update(['stock' => (float) Item::query()->where('parent_id', $itemId)->sum('stock')]);

            return;
        }
        Item::query()->withTrashed()->whereKey($itemId)->update(['stock' => (float) Stock::query()->where('item_id', $itemId)->sum('qty')]);
        if ($item->parent_id) {
            self::syncItemTotal((int) $item->parent_id);
        }
    }

    public static function available(int $itemId, ?int $warehouseId = null): float
    {
        return (float) Stock::query()->where('item_id', $itemId)->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))->sum('qty');
    }

    public static function fmt(float $qty): string
    {
        return rtrim(rtrim(number_format($qty, 3, '.', ''), '0'), '.');
    }

    /** Next document number: PREFIX-0001 based on the highest existing one. */
    public static function nextNumber(string $modelClass, string $column, string $prefix): string
    {
        $last = $modelClass::query()->where($column, 'like', $prefix.'%')->orderByDesc('id')->value($column);
        $n = $last ? ((int) preg_replace('/\D/', '', substr($last, strlen($prefix)))) + 1 : 1;

        return $prefix.str_pad((string) $n, 4, '0', STR_PAD_LEFT);
    }
}
