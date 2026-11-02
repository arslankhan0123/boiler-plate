<?php

declare(strict_types=1);

namespace App\Http\Controllers\Retail;

use App\Http\Controllers\Inventory\ItemController;
use App\Models\Inventory\Attribute;
use App\Models\Inventory\Item;
use App\Models\Inventory\Warehouse;
use App\Models\Retail\Customer;
use App\Models\Retail\Supplier;
use App\Models\Settings\TaxRate;
use App\Support\Inventory\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** CSV import for items, customers and suppliers (with a sample file for each). */
class ImportController extends RetailController
{
    public const TYPES = [
        'items' => ['Items', 'inventory.manage_products', 'inventory.items.index',
            ['name', 'code', 'barcode', 'sku', 'hsn', 'category', 'brand', 'unit', 'cost_price', 'tax_rate', 'tax_type', 'profit_margin', 'sales_price', 'mrp', 'alert_qty', 'opening_qty', 'expire_date', 'is_service', 'description'],
            ['Juice 1L', '', '8901234567890', 'JUI-1L', '', 'Drinks', 'Nestle', 'Pcs', '100', '18', 'exclusive', '25', '', '160', '5', '50', '', '0', '']],
        'customers' => ['Customers', 'sales.customers', 'sales.customers.index',
            ['name', 'contact_person', 'mobile', 'phone', 'email', 'tax_number', 'address', 'city', 'opening_balance', 'credit_limit'],
            ['Ali Traders', 'Ali', '03001234567', '', 'ali@example.com', '', 'Main Bazar', 'Lahore', '0', '50000']],
        'suppliers' => ['Suppliers', 'purchase.suppliers', 'purchase.suppliers.index',
            ['name', 'contact_person', 'phone', 'email', 'tax_number', 'address', 'city', 'opening_balance'],
            ['Supplier Co', 'Bilal', '04212345678', 'sales@supplier.com', '', 'Industrial Area', 'Karachi', '0']],
    ];

    public function form(string $type)
    {
        abort_unless(isset(self::TYPES[$type]), 404);
        $this->need(self::TYPES[$type][1]);

        return view('retail.import', ['type' => $type, 'meta' => self::TYPES[$type]]);
    }

    public function sample(string $type)
    {
        abort_unless(isset(self::TYPES[$type]), 404);
        $this->need(self::TYPES[$type][1]);
        [, , , $cols, $example] = self::TYPES[$type];

        return response()->streamDownload(function () use ($cols, $example) {
            $o = fopen('php://output', 'w');
            fputcsv($o, $cols);
            fputcsv($o, $example);
            fclose($o);
        }, "$type-sample.csv", ['Content-Type' => 'text/csv']);
    }

    public function store(Request $request, string $type)
    {
        abort_unless(isset(self::TYPES[$type]), 404);
        $this->need(self::TYPES[$type][1]);
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:5120']]);

        $fh = fopen($request->file('file')->getRealPath(), 'r');
        $head = array_map(fn ($h) => strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $h))), fgetcsv($fh) ?: []);
        if (! in_array('name', $head, true)) {
            return back()->with('error', 'The first row must be the column headings and include "name". Download the sample file.');
        }
        $done = 0;
        $errors = [];
        $line = 1;
        DB::beginTransaction();
        try {
            while (($row = fgetcsv($fh)) !== false) {
                $line++;
                if (! array_filter($row, fn ($v) => trim((string) $v) !== '')) {
                    continue;
                }
                $r = array_map(fn ($v) => trim((string) $v), array_combine($head, array_pad(array_slice($row, 0, count($head)), count($head), '')));
                try {
                    $this->{'import'.ucfirst($type)}($r);
                    $done++;
                } catch (\Throwable $e) {
                    $errors[] = "Row $line: ".$e->getMessage();
                }
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Import failed: '.$e->getMessage());
        }
        fclose($fh);

        return back()->with($errors ? 'error' : 'success', "$done row(s) imported.".($errors ? ' '.count($errors).' skipped.' : ''))->with('importErrors', array_slice($errors, 0, 50));
    }

    private function attr(string $type, string $name): ?int
    {
        return $name === '' ? null : Attribute::query()->firstOrCreate(['type' => $type, 'name' => $name], ['is_active' => true])->id;
    }

    private function importItems(array $r): void
    {
        if ($r['name'] === '') {
            throw new \RuntimeException('name is required');
        }
        if (! empty($r['code']) && Item::query()->withTrashed()->where('code', $r['code'])->exists()) {
            throw new \RuntimeException("item code {$r['code']} already exists");
        }
        $rate = (float) ($r['tax_rate'] ?? 0);
        $tax = $rate > 0 ? TaxRate::query()->where('rate', $rate)->first() ?? TaxRate::create(['name' => 'Tax '.$rate.'%', 'rate' => $rate, 'type' => 'exclusive', 'is_active' => true]) : null;
        $taxType = in_array(strtolower($r['tax_type'] ?? ''), ['inclusive', 'exclusive'], true) ? strtolower($r['tax_type']) : 'exclusive';
        $prices = ItemController::prices((float) ($r['cost_price'] ?? 0), $rate, $taxType, ($r['profit_margin'] ?? '') === '' ? null : $r['profit_margin'], ($r['sales_price'] ?? '') === '' ? null : $r['sales_price']);
        $service = in_array(strtolower($r['is_service'] ?? ''), ['1', 'yes', 'true', 'y'], true);
        $code = ($r['code'] ?? '') ?: StockService::nextNumber(Item::class, 'code', 'IT');
        $item = new Item(array_merge($prices, [
            'name' => $r['name'], 'code' => $code, 'barcode' => ($r['barcode'] ?? '') ?: $code, 'sku' => ($r['sku'] ?? '') ?: null, 'hsn' => $r['hsn'] ?? null,
            'category_id' => $this->attr('category', $r['category'] ?? ''), 'brand_id' => $this->attr('brand', $r['brand'] ?? ''), 'unit_id' => $this->attr('unit', $r['unit'] ?? ''),
            'tax_id' => $tax?->id, 'tax_type' => $taxType, 'mrp' => ($r['mrp'] ?? '') === '' ? null : (float) $r['mrp'], 'alert_qty' => (float) ($r['alert_qty'] ?? 0),
            'expire_date' => ($r['expire_date'] ?? '') ?: null, 'is_service' => $service, 'item_group' => 'single', 'description' => $r['description'] ?? null,
            'discount_type' => 'percentage', 'is_active' => true, 'created_by' => auth()->id(),
        ]));
        $item->save();
        $qty = (float) ($r['opening_qty'] ?? 0);
        if ($qty != 0.0 && ! $service) {
            StockService::move($item, Warehouse::default()->id, $qty, 'opening', $item, 'Opening stock (import)');
            Item::query()->whereKey($item->id)->update(['opening_stock' => $qty]);
        }
    }

    private function importCustomers(array $r): void
    {
        if ($r['name'] === '') {
            throw new \RuntimeException('name is required');
        }
        Customer::create([
            'company_name' => $r['name'], 'contact_person' => $r['contact_person'] ?? null, 'mobile' => $r['mobile'] ?? null, 'phone' => $r['phone'] ?? null,
            'email' => ($r['email'] ?? '') ?: null, 'vat_number' => $r['tax_number'] ?? null, 'address' => $r['address'] ?? null, 'city' => $r['city'] ?? null,
            'opening_balance' => (float) ($r['opening_balance'] ?? 0), 'credit_limit' => (float) ($r['credit_limit'] ?? 0), 'is_active' => true, 'created_by' => auth()->id(),
            'code' => StockService::nextNumber(Customer::class, 'code', 'CU'),
        ]);
    }

    private function importSuppliers(array $r): void
    {
        if ($r['name'] === '') {
            throw new \RuntimeException('name is required');
        }
        Supplier::create([
            'company_name' => $r['name'], 'contact_person' => $r['contact_person'] ?? null, 'phone' => $r['phone'] ?? null, 'email' => ($r['email'] ?? '') ?: null,
            'vat_number' => $r['tax_number'] ?? null, 'street' => $r['address'] ?? null, 'city' => $r['city'] ?? null,
            'opening_balance' => (float) ($r['opening_balance'] ?? 0), 'is_active' => true, 'created_by' => auth()->id(),
        ]);
    }
}
