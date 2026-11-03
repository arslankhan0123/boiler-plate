<?php

declare(strict_types=1);

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\Order;
use App\Models\Ecommerce\Product;
use App\Models\Inventory\Item;
use App\Models\Inventory\Warehouse;
use App\Models\Retail\Document;
use App\Models\User;
use App\Support\Accounting\Ledger;
use App\Support\Settings;
use Illuminate\Support\Facades\DB;

/**
 * Reports & Analytics — cross-module overview dashboards.
 * The module registry only shows a feature when its underlying module is enabled
 * for the tenant and the user holds reports.view, so each page assumes that gate
 * has already passed (route middleware: module:reports,<feature>).
 */
class ReportsController extends Controller
{
    protected function need(string ...$permissions): void
    {
        $user = auth()->user();
        foreach ($permissions as $p) {
            if ($user?->can($p)) {
                return;
            }
        }
        abort(403, 'You do not have permission to view reports.');
    }

    private function meta(): array
    {
        $cur = strtoupper((string) Settings::get('default_currency', 'PKR'));

        return ['cur' => $cur];
    }

    /** Month labels + bounds for the last N months (oldest first). */
    private function months(int $n = 6): array
    {
        $out = [];
        for ($i = $n - 1; $i >= 0; $i--) {
            $m = now()->startOfMonth()->subMonths($i);
            $out[] = ['label' => $m->format('M'), 'start' => $m->toDateString(), 'end' => $m->copy()->endOfMonth()->toDateString()];
        }

        return $out;
    }

    // ───────────────────────── Sales & Purchase ─────────────────────────

    public function sales()
    {
        $this->need('reports.view');

        $inv = fn () => Document::query()->where('type', 'invoice')->where('status', 'final');
        $salesRevenue = (float) $inv()->sum('grand_total');
        $salesCount = (int) $inv()->count();
        $purchaseCost = (float) Document::where('type', 'purchase')->sum('grand_total');
        $purchaseCount = (int) Document::where('type', 'purchase')->count();
        $returns = (float) Document::where('type', 'sale_return')->where('status', 'final')->sum('grand_total');

        $trend = [];
        foreach ($this->months(6) as $m) {
            $trend[] = [
                'label' => $m['label'],
                'sales' => round((float) $inv()->whereBetween('date', [$m['start'], $m['end']])->sum('grand_total'), 2),
                'purchases' => round((float) Document::where('type', 'purchase')->whereBetween('date', [$m['start'], $m['end']])->sum('grand_total'), 2),
            ];
        }

        $topCustomers = $inv()->whereNotNull('customer_id')
            ->selectRaw('customer_id, COUNT(*) n, SUM(grand_total) total')
            ->groupBy('customer_id')->orderByDesc('total')->limit(8)->with('customer')->get();

        return view('reports.sales', $this->meta() + [
            'salesRevenue' => $salesRevenue, 'salesCount' => $salesCount,
            'purchaseCost' => $purchaseCost, 'purchaseCount' => $purchaseCount,
            'returns' => $returns, 'grossProfit' => round($salesRevenue - $returns - $purchaseCost, 2),
            'avgSale' => $salesCount ? round($salesRevenue / $salesCount, 2) : 0,
            'trend' => $trend, 'topCustomers' => $topCustomers,
        ]);
    }

    // ───────────────────────── Inventory ─────────────────────────

    public function inventory()
    {
        $this->need('reports.view');

        $rows = DB::connection('tenant')->table('inv_stocks as s')
            ->join('inv_items as i', 'i.id', '=', 's.item_id')
            ->selectRaw('i.id, i.name, i.sku, SUM(s.qty) qty, i.purchase_price, i.sales_price')
            ->groupBy('i.id', 'i.name', 'i.sku', 'i.purchase_price', 'i.sales_price')->get();

        $stockValue = round($rows->sum(fn ($r) => (float) $r->qty * (float) $r->purchase_price), 2);
        $retailValue = round($rows->sum(fn ($r) => (float) $r->qty * (float) $r->sales_price), 2);
        $totalQty = round($rows->sum('qty'), 2);
        $lowStock = $rows->filter(fn ($r) => $r->qty > 0 && $r->qty <= 5)->count();
        $outOfStock = $rows->filter(fn ($r) => $r->qty <= 0)->count();

        $topByValue = $rows->sortByDesc(fn ($r) => (float) $r->qty * (float) $r->purchase_price)->take(10)->values();

        $byWarehouse = DB::connection('tenant')->table('inv_stocks as s')
            ->join('inv_warehouses as w', 'w.id', '=', 's.warehouse_id')
            ->join('inv_items as i', 'i.id', '=', 's.item_id')
            ->selectRaw('w.name, SUM(s.qty) qty, SUM(s.qty * i.purchase_price) value')
            ->groupBy('w.name')->orderByDesc('value')->get();

        return view('reports.inventory', $this->meta() + [
            'itemCount' => Item::count(),
            'totalQty' => $totalQty, 'stockValue' => $stockValue, 'retailValue' => $retailValue,
            'lowStock' => $lowStock, 'outOfStock' => $outOfStock,
            'topByValue' => $topByValue, 'byWarehouse' => $byWarehouse,
        ]);
    }

    // ───────────────────────── POS ─────────────────────────

    public function pos()
    {
        $this->need('reports.view');

        $pos = fn () => Document::query()->where('is_pos', true)->where('type', 'invoice')->where('status', 'final');
        $todaySales = (float) $pos()->whereDate('date', today())->sum('grand_total');
        $todayCount = (int) $pos()->whereDate('date', today())->count();
        $totalSales = (float) $pos()->sum('grand_total');
        $totalCount = (int) $pos()->count();

        $trend = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = today()->subDays($i);
            $trend[] = ['label' => $d->format('D'), 'sales' => round((float) $pos()->whereDate('date', $d->toDateString())->sum('grand_total'), 2)];
        }

        $byCashier = $pos()->whereNotNull('created_by')
            ->selectRaw('created_by, COUNT(*) n, SUM(grand_total) total')
            ->groupBy('created_by')->orderByDesc('total')->limit(8)->get();
        $names = User::whereIn('id', $byCashier->pluck('created_by'))->pluck('name', 'id');

        return view('reports.pos', $this->meta() + [
            'todaySales' => $todaySales, 'todayCount' => $todayCount,
            'totalSales' => $totalSales, 'totalCount' => $totalCount,
            'avgTicket' => $totalCount ? round($totalSales / $totalCount, 2) : 0,
            'trend' => $trend, 'byCashier' => $byCashier, 'cashierNames' => $names,
        ]);
    }

    // ───────────────────────── E-commerce ─────────────────────────

    public function ecommerce()
    {
        $this->need('reports.view');

        $valid = fn () => Order::query()->whereNotIn('status', ['cancelled']);
        $orderCount = (int) $valid()->count();
        $revenue = (float) $valid()->sum('grand_total');

        $trend = [];
        foreach ($this->months(6) as $m) {
            $trend[] = ['label' => $m['label'], 'sales' => round((float) $valid()->whereBetween('placed_at', [$m['start'].' 00:00:00', $m['end'].' 23:59:59'])->sum('grand_total'), 2)];
        }

        $topProducts = DB::connection('tenant')->table('ecom_order_items')
            ->selectRaw('name, SUM(qty) qty, SUM(line_total) total')
            ->groupBy('name')->orderByDesc('qty')->limit(10)->get();

        return view('reports.ecommerce', $this->meta() + [
            'orderCount' => $orderCount, 'revenue' => $revenue,
            'avgOrder' => $orderCount ? round($revenue / $orderCount, 2) : 0,
            'published' => Product::where('is_published', true)->count(),
            'pending' => Order::where('status', 'pending')->count(),
            'paid' => Order::where('payment_status', 'paid')->count(),
            'byStatus' => Order::query()->selectRaw('status, COUNT(*) n, SUM(grand_total) total')->groupBy('status')->get(),
            'topProducts' => $topProducts, 'trend' => $trend,
        ]);
    }

    // ───────────────────────── Accounting ─────────────────────────

    public function accounting()
    {
        $this->need('reports.view');

        $from = now()->subMonths(11)->startOfMonth()->toDateString();
        $to = today()->toDateString();
        $pl = Ledger::profitLoss($from, $to);
        $bs = Ledger::balanceSheet($to);

        return view('reports.accounting', $this->meta() + [
            'from' => $from, 'to' => $to,
            'income' => $pl['total_income'], 'expense' => $pl['total_expense'], 'net' => $pl['net'],
            'incomeRows' => $pl['income'], 'expenseRows' => $pl['expense'],
            'assets' => $bs['total_assets'], 'liabilities' => $bs['total_liabilities'], 'equity' => $bs['total_equity'],
            'balanced' => $bs['balanced'],
        ]);
    }

    // ───────────────────────── HR ─────────────────────────

    public function hr()
    {
        $this->need('reports.view');

        $tid = auth()->user()->tenant_id;
        $employees = User::where('tenant_id', $tid)->count();

        $leaveByStatus = DB::connection('tenant')->table('hrm_leave_requests')
            ->selectRaw('status, COUNT(*) n')->groupBy('status')->pluck('n', 'status');
        $pendingLeave = (int) ($leaveByStatus['pending'] ?? 0);
        $approvedLeave = (int) ($leaveByStatus['approved'] ?? 0);

        $safeCount = function (string $table, ?callable $where = null): int {
            try {
                $q = DB::connection('tenant')->table($table);
                if ($where) {
                    $where($q);
                }

                return (int) $q->count();
            } catch (\Throwable) {
                return 0;
            }
        };
        $presentToday = $safeCount('hrm_attendances', fn ($q) => $q->whereDate('date', today()));
        $openJobs = $safeCount('hrm_jobs', fn ($q) => $q->whereIn('status', ['open', 'published', 'active']));
        $applications = $safeCount('hrm_job_applications');
        $payrollRuns = $safeCount('hrm_payroll_runs');

        $recentLeaves = DB::connection('tenant')->table('hrm_leave_requests')
            ->orderByDesc('start_date')->limit(8)->get();

        return view('reports.hr', $this->meta() + [
            'employees' => $employees,
            'pendingLeave' => $pendingLeave, 'approvedLeave' => $approvedLeave,
            'presentToday' => $presentToday, 'openJobs' => $openJobs,
            'applications' => $applications, 'payrollRuns' => $payrollRuns,
            'leaveByStatus' => $leaveByStatus, 'recentLeaves' => $recentLeaves,
        ]);
    }
}
