<?php

declare(strict_types=1);

namespace App\Http\Controllers\Inventory;

use App\Models\Inventory\Transfer;
use App\Models\Inventory\Warehouse;
use App\Models\User;
use App\Support\Inventory\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Move stock from one warehouse to another (out of the source, into the destination). */
class TransferController extends InventoryController
{
    public function index(Request $request)
    {
        $this->need('inventory.view', 'inventory.transfer');
        $rows = Transfer::query()->with('from', 'to')->withCount('items')->withSum('items', 'qty')
            ->when($request->q, fn ($q, $s) => $q->where(fn ($w) => $w->where('reference_no', 'like', "%$s%")->orWhere('note', 'like', "%$s%")))
            ->when($request->warehouse, fn ($q, $v) => $q->where(fn ($w) => $w->where('from_warehouse_id', $v)->orWhere('to_warehouse_id', $v)))
            ->when($request->from, fn ($q, $v) => $q->whereDate('date', '>=', $v))
            ->when($request->to, fn ($q, $v) => $q->whereDate('date', '<=', $v))
            ->latest('date')->latest('id')->paginate(25)->withQueryString();

        return view('inventory.transfers.index', [
            'rows' => $rows, 'warehouses' => Warehouse::options(),
            'users' => User::query()->whereIn('id', $rows->pluck('created_by')->filter())->pluck('name', 'id'),
            'canEdit' => $this->can('inventory.transfer'),
        ]);
    }

    public function create()
    {
        $this->need('inventory.transfer');
        $options = Warehouse::options();
        if (count($options) < 2) {
            return redirect()->route('inventory.warehouses.index')->with('error', 'Add a second warehouse first — a transfer needs two.');
        }

        return view('inventory.transfers.form', $this->form(new Transfer([
            'date' => today(), 'from_warehouse_id' => Warehouse::default()->id,
            'reference_no' => StockService::nextNumber(Transfer::class, 'reference_no', 'TRF-'),
        ])));
    }

    public function edit(Transfer $transfer)
    {
        $this->need('inventory.transfer');

        return view('inventory.transfers.form', $this->form($transfer->load('items.item.variant', 'items.item.stocks')));
    }

    public function show(Transfer $transfer)
    {
        $this->need('inventory.view');

        return view('inventory.transfers.show', [
            'doc' => $transfer->load('items.item.unit', 'items.item.variant', 'from', 'to'),
            'by' => User::query()->whereKey($transfer->created_by)->value('name'),
            'canEdit' => $this->can('inventory.transfer'),
        ]);
    }

    public function store(Request $request)
    {
        $this->need('inventory.transfer');
        $doc = $this->save($request, new Transfer());

        return redirect()->route('inventory.transfers.show', $doc)->with('success', 'Stock transferred.');
    }

    public function update(Request $request, Transfer $transfer)
    {
        $this->need('inventory.transfer');
        $this->save($request, $transfer);

        return redirect()->route('inventory.transfers.show', $transfer)->with('success', 'Transfer updated.');
    }

    public function destroy(Transfer $transfer)
    {
        $this->need('inventory.transfer');
        DB::transaction(function () use ($transfer) {
            StockService::reverse($transfer);
            $transfer->delete();
        });

        return redirect()->route('inventory.transfers.index')->with('success', 'Transfer deleted — stock moved back.');
    }

    private function form(Transfer $doc): array
    {
        return [
            'doc' => $doc, 'warehouses' => Warehouse::options(),
            'lines' => $doc->exists ? $doc->items->map(fn ($l) => ItemController::json($l->item, $doc->from_warehouse_id) + ['qty' => $l->qty])->values() : collect(),
        ];
    }

    private function save(Request $request, Transfer $doc): Transfer
    {
        $data = $request->validate([
            'reference_no' => ['required', 'string', 'max:50', 'unique:inv_transfers,reference_no'.($doc->exists ? ','.$doc->id : '')],
            'from_warehouse_id' => ['required', 'integer', 'exists:inv_warehouses,id'],
            'to_warehouse_id' => ['required', 'integer', 'exists:inv_warehouses,id', 'different:from_warehouse_id'],
            'date' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array'],
        ], ['to_warehouse_id.different' => 'Choose two different warehouses.']);
        $rows = $this->rows($data['items']);
        if (! $rows) {
            throw \Illuminate\Validation\ValidationException::withMessages(['items' => 'Add at least one item with a quantity.']);
        }

        return DB::transaction(function () use ($doc, $data, $rows) {
            if ($doc->exists) {
                StockService::reverse($doc);
                $doc->items()->delete();
            }
            $doc->fill(collect($data)->except('items')->all() + ['created_by' => $doc->created_by ?? auth()->id()])->save();
            foreach ($rows as $r) {
                $doc->items()->create(['item_id' => $r['item_id'], 'qty' => $r['qty']]);
                StockService::move($r['item_id'], (int) $data['from_warehouse_id'], -$r['qty'], 'transfer_out', $doc, 'To warehouse #'.$data['to_warehouse_id'], $data['date'], allowNegative: false);
                StockService::move($r['item_id'], (int) $data['to_warehouse_id'], $r['qty'], 'transfer_in', $doc, 'From warehouse #'.$data['from_warehouse_id'], $data['date']);
            }

            return $doc;
        });
    }
}
