<?php

declare(strict_types=1);

namespace App\Models\Retail;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\UsesTenantConnection;
use App\Models\Inventory\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * One engine for every retail document. TYPES says, per type: side (sale/purchase), number prefix,
 * stock effect (+1 in, -1 out, 0 none), whether it takes payments, statuses and permissions.
 */
class Document extends Model
{
    use Auditable;
    use SoftDeletes;
    use UsesTenantConnection;

    public const TYPES = [
        'invoice' => [
            'side' => 'sale', 'one' => 'Sales Invoice', 'many' => 'Sales Invoices', 'prefix' => 'SL', 'stock' => -1, 'payments' => true,
            'statuses' => ['final' => 'Final', 'draft' => 'Draft'], 'stock_statuses' => ['final'], 'route' => 'sales.invoices',
            'perm' => 'sales.view', 'perm_create' => 'sales.create', 'perm_edit' => 'sales.update', 'perm_delete' => 'sales.delete', 'icon' => 'receipt',
        ],
        'quotation' => [
            'side' => 'sale', 'one' => 'Quotation', 'many' => 'Quotations', 'prefix' => 'QT', 'stock' => 0, 'payments' => false,
            'statuses' => ['open' => 'Open', 'converted' => 'Converted to sale', 'cancelled' => 'Cancelled'], 'stock_statuses' => [], 'route' => 'sales.quotations',
            'perm' => 'sales.quotations', 'perm_create' => 'sales.quotations', 'perm_edit' => 'sales.quotations', 'perm_delete' => 'sales.quotations', 'icon' => 'file-earmark-text',
        ],
        'delivery' => [
            'side' => 'sale', 'one' => 'Delivery Note', 'many' => 'Delivery Notes', 'prefix' => 'DN', 'stock' => 0, 'payments' => false,
            'statuses' => ['pending' => 'Pending', 'delivered' => 'Delivered', 'converted' => 'Invoiced'], 'stock_statuses' => [], 'route' => 'sales.deliveries',
            'perm' => 'sales.delivery_notes', 'perm_create' => 'sales.delivery_notes', 'perm_edit' => 'sales.delivery_notes', 'perm_delete' => 'sales.delivery_notes', 'icon' => 'truck',
        ],
        'sale_return' => [
            'side' => 'sale', 'one' => 'Sales Return', 'many' => 'Sales Returns', 'prefix' => 'SR', 'stock' => 1, 'payments' => true,
            'statuses' => ['final' => 'Returned'], 'stock_statuses' => ['final'], 'route' => 'sales.returns',
            'perm' => 'sales.returns', 'perm_create' => 'sales.returns', 'perm_edit' => 'sales.returns', 'perm_delete' => 'sales.returns', 'icon' => 'arrow-return-left',
        ],
        'rfq' => [
            'side' => 'purchase', 'one' => 'Request for Quotation', 'many' => 'Requests for Quotation', 'prefix' => 'RFQ', 'stock' => 0, 'payments' => false,
            'statuses' => ['open' => 'Open', 'converted' => 'Ordered', 'cancelled' => 'Cancelled'], 'stock_statuses' => [], 'route' => 'purchase.rfq',
            'perm' => 'purchase.rfq', 'perm_create' => 'purchase.rfq', 'perm_edit' => 'purchase.rfq', 'perm_delete' => 'purchase.rfq', 'icon' => 'question-square',
        ],
        'order' => [
            'side' => 'purchase', 'one' => 'Purchase Order', 'many' => 'Purchase Orders', 'prefix' => 'PO', 'stock' => 0, 'payments' => false,
            'statuses' => ['ordered' => 'Ordered', 'converted' => 'Received (purchase made)', 'cancelled' => 'Cancelled'], 'stock_statuses' => [], 'route' => 'purchase.orders',
            'perm' => 'purchase.orders', 'perm_create' => 'purchase.orders', 'perm_edit' => 'purchase.orders', 'perm_delete' => 'purchase.orders', 'icon' => 'clipboard-check',
        ],
        'purchase' => [
            'side' => 'purchase', 'one' => 'Purchase', 'many' => 'Purchases', 'prefix' => 'PU', 'stock' => 1, 'payments' => true,
            'statuses' => ['received' => 'Received', 'pending' => 'Pending', 'ordered' => 'Ordered'], 'stock_statuses' => ['received'], 'route' => 'purchase.purchases',
            'perm' => 'purchase.view', 'perm_create' => 'purchase.create', 'perm_edit' => 'purchase.update', 'perm_delete' => 'purchase.delete', 'icon' => 'cart-plus',
        ],
        'purchase_return' => [
            'side' => 'purchase', 'one' => 'Purchase Return', 'many' => 'Purchase Returns', 'prefix' => 'PR', 'stock' => -1, 'payments' => true,
            'statuses' => ['final' => 'Returned'], 'stock_statuses' => ['final'], 'route' => 'purchase.returns',
            'perm' => 'purchase.returns', 'perm_create' => 'purchase.returns', 'perm_edit' => 'purchase.returns', 'perm_delete' => 'purchase.returns', 'icon' => 'arrow-return-right',
        ],
        'hold' => [
            'side' => 'sale', 'one' => 'Held Invoice', 'many' => 'Held Invoices', 'prefix' => 'HD', 'stock' => 0, 'payments' => false,
            'statuses' => ['held' => 'Held'], 'stock_statuses' => [], 'route' => 'pos',
            'perm' => 'pos.hold', 'perm_create' => 'pos.hold', 'perm_edit' => 'pos.hold', 'perm_delete' => 'pos.hold', 'icon' => 'pause-circle',
        ],
    ];

    protected $table = 'rt_documents';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return collect(['subtotal', 'tax_total', 'item_discount_total', 'other_charges_input', 'other_charges_amt', 'discount_input', 'discount_amt', 'coupon_amt', 'round_off', 'grand_total', 'paid_amount', 'previous_due'])
            ->mapWithKeys(fn ($c) => [$c => 'float'])->all() + ['date' => 'date', 'due_date' => 'date', 'is_pos' => 'boolean', 'update_prices' => 'boolean', 'meta' => 'array'];
    }

    public static function meta(string $type): array
    {
        abort_unless(isset(self::TYPES[$type]), 404);

        return self::TYPES[$type] + ['type' => $type];
    }

    public function info(): array
    {
        return self::meta($this->type);
    }

    public function isSale(): bool
    {
        return self::TYPES[$this->type]['side'] === 'sale';
    }

    public function items(): HasMany
    {
        return $this->hasMany(DocumentItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->orderBy('date')->orderBy('id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class)->withTrashed();
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function salesman(): BelongsTo
    {
        return $this->belongsTo(Salesman::class)->withTrashed();
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id')->withTrashed();
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function partyName(): string
    {
        return $this->isSale() ? ($this->customer->company_name ?? 'Walk-in') : ($this->supplier->company_name ?? '—');
    }

    public function due(): float
    {
        return round(max(0, $this->grand_total - $this->paid_amount), 4);
    }

    /**
     * Tax collected per tax name, splitting any tax group into its components.
     * @return array<string, float>
     */
    public function taxSummary(): array
    {
        $this->loadMissing('items');
        $taxes = \App\Models\Settings\TaxRate::query()->whereIn('id', $this->items->pluck('tax_id')->filter()->unique())->get()->keyBy('id');
        $out = [];
        foreach ($this->items as $l) {
            if ($l->tax_amt <= 0) {
                continue;
            }
            $tax = $l->tax_id ? ($taxes[$l->tax_id] ?? null) : null;
            $parts = $tax ? $tax->split((float) $l->tax_amt) : ['Tax ('.rtrim(rtrim(number_format((float) $l->tax_rate, 2), '0'), '.').'%)' => (float) $l->tax_amt];
            foreach ($parts as $name => $amt) {
                $out[$name] = round(($out[$name] ?? 0) + $amt, 4);
            }
        }

        return $out;
    }

    public function affectsStock(): bool
    {
        return self::TYPES[$this->type]['stock'] !== 0 && in_array($this->status, self::TYPES[$this->type]['stock_statuses'], true);
    }
}
