<?php

declare(strict_types=1);

namespace App\Support\Retail;

use App\Models\Inventory\Item;
use App\Models\Retail\Customer;
use App\Models\Retail\Document;
use App\Models\Retail\DocumentItem;
use App\Models\Retail\Supplier;
use App\Models\User;
use App\Support\ModuleAccess;

/** Retail numbers for the dashboard — each block only when the user may see it. */
class Dashboard
{
    public static function data(User $user): ?array
    {
        if (! $user->tenant_id) {
            return null;
        }
        $sales = ModuleAccess::moduleAvailable($user, 'sales') && ($user->can('sales.view') || $user->can('sales.reports'));
        $purchase = ModuleAccess::moduleAvailable($user, 'purchase') && ($user->can('purchase.view') || $user->can('purchase.reports'));
        $inventory = ModuleAccess::moduleAvailable($user, 'inventory') && $user->can('inventory.view');
        if (! $sales && ! $purchase && ! $inventory) {
            return null;
        }

        $inv = fn () => Document::query()->where('type', 'invoice')->where('status', 'final');
        $out = ['sales' => $sales, 'purchase' => $purchase, 'inventory' => $inventory];

        if ($sales) {
            $out['today'] = (float) $inv()->whereDate('date', today())->sum('grand_total');
            $out['todayCount'] = $inv()->whereDate('date', today())->count();
            $out['month'] = (float) $inv()->whereBetween('date', [today()->startOfMonth(), today()])->sum('grand_total');
            $out['receivable'] = array_sum(Ledger::dues('customer', Customer::query()->get(['id', 'opening_balance'])));
            $out['recent'] = $inv()->with('customer')->latest('id')->limit(6)->get();
            $out['top'] = DocumentItem::query()->join('rt_documents as d', 'd.id', '=', 'rt_document_items.document_id')->join('inv_items as i', 'i.id', '=', 'rt_document_items.item_id')
                ->where('d.type', 'invoice')->where('d.status', 'final')->whereNull('d.deleted_at')->where('d.date', '>=', today()->startOfMonth())
                ->groupBy('i.id', 'i.name')->selectRaw('i.name, SUM(rt_document_items.qty) qty, SUM(rt_document_items.total) amount')->orderByDesc('amount')->limit(5)->get();
            $out['chart'] = collect(range(6, 0))->map(fn ($d) => ['day' => today()->subDays($d)->format('D'), 'total' => (float) $inv()->whereDate('date', today()->subDays($d))->sum('grand_total')])->all();
        }
        if ($purchase) {
            $out['purchaseMonth'] = (float) Document::query()->where('type', 'purchase')->whereBetween('date', [today()->startOfMonth(), today()])->sum('grand_total');
            $out['payable'] = array_sum(Ledger::dues('supplier', Supplier::query()->get(['id', 'opening_balance'])));
        }
        if ($inventory) {
            $low = Item::query()->sellable()->where('is_service', false)->where('alert_qty', '>', 0)->whereColumn('stock', '<=', 'alert_qty');
            $out['lowCount'] = (clone $low)->count();
            $out['low'] = (clone $low)->with('variant', 'unit')->orderBy('stock')->limit(6)->get();
            $out['expiring'] = Item::query()->whereNotNull('expire_date')->whereDate('expire_date', '<=', today()->addDays(30))->count();
        }

        return $out;
    }
}
