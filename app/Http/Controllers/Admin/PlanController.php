<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Billing\Plan;
use App\Support\Billing\PlatformPayments;
use App\Support\Billing\PlatformPayPal;
use App\Support\Billing\PlatformSettings;
use App\Support\ModuleRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Platform-only: the master admin creates and edits subscription plans. A plan
 * is a bundle of business modules with a monthly and yearly price. Plans can be
 * mirrored onto Stripe/PayPal so recurring card subscriptions bill against them.
 */
class PlanController extends Controller
{
    public function index()
    {
        $plans = Plan::query()->ordered()->withCount('subscriptions')->get();

        return view('admin.plans.index', [
            'plans' => $plans,
            'gatewaysReady' => PlatformSettings::anyGatewayReady(),
        ]);
    }

    public function create()
    {
        ModuleRegistry::sync();

        return view('admin.plans.form', [
            'plan' => new Plan(['is_active' => true, 'is_public' => true, 'currency' => PlatformSettings::currency(), 'modules' => []]),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $plan = Plan::query()->create($data);

        $this->maybeSync($request, $plan);

        return redirect()->route('plans.index')->with('success', "Plan \"{$plan->name}\" created.");
    }

    public function edit(Plan $plan)
    {
        ModuleRegistry::sync();

        return view('admin.plans.form', compact('plan'));
    }

    public function update(Request $request, Plan $plan)
    {
        $data = $this->validated($request, $plan);

        $plan->update($data);

        $this->maybeSync($request, $plan);

        return redirect()->route('plans.index')->with('success', "Plan \"{$plan->name}\" updated.");
    }

    public function destroy(Plan $plan)
    {
        if ($plan->subscriptions()->whereIn('status', ['active', 'pending'])->exists()) {
            return back()->with('error', 'This plan has active subscriptions and cannot be deleted. Make it inactive instead.');
        }
        $name = $plan->name;
        $plan->delete();

        return redirect()->route('plans.index')->with('success', "Plan \"{$name}\" deleted.");
    }

    /** Create the plan's Stripe/PayPal product + prices on demand. */
    public function sync(Plan $plan)
    {
        $done = [];
        try {
            if (PlatformSettings::stripeReady()) {
                $ids = PlatformPayments::syncPlan($plan);
                if ($ids) {
                    $plan->update($ids);
                    $done[] = 'Stripe';
                }
            }
            if (PlatformSettings::paypalReady()) {
                $ids = PlatformPayPal::syncPlan($plan);
                if ($ids) {
                    $plan->update($ids);
                    $done[] = 'PayPal';
                }
            }
        } catch (Throwable $e) {
            report($e);

            return back()->with('error', 'Gateway sync failed: '.$e->getMessage());
        }

        return back()->with('success', $done
            ? ('Plan synced to '.implode(' & ', $done).'.')
            : 'Nothing to sync (no gateway configured, or already synced).');
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    private function validated(Request $request, ?Plan $plan = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'modules' => ['required', 'array', 'min:1'],
            'modules.*' => [Rule::in(array_keys(ModuleRegistry::assignable()))],
            'price_monthly' => ['required', 'numeric', 'min:0', 'max:9999999'],
            'price_yearly' => ['required', 'numeric', 'min:0', 'max:9999999'],
            'currency' => ['required', 'string', 'size:3'],
            'is_active' => ['required', 'boolean'],
            'is_public' => ['required', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ], [], ['modules' => 'modules']);

        $modules = array_values(array_unique($data['modules']));
        if ($errors = ModuleRegistry::validateSelection($modules)) {
            throw ValidationException::withMessages(['modules' => $errors]);
        }

        $slug = Str::slug($data['name']);
        $base = $slug ?: 'plan';
        $slug = $base;
        $i = 1;
        $existsQuery = fn ($s) => Plan::query()->where('slug', $s)->when($plan, fn ($q) => $q->where('id', '!=', $plan->id))->exists();
        while ($existsQuery($slug)) {
            $slug = $base.'-'.(++$i);
        }

        return [
            'name' => $data['name'],
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'modules' => $modules,
            'price_monthly' => round((float) $data['price_monthly'], 2),
            'price_yearly' => round((float) $data['price_yearly'], 2),
            'currency' => strtoupper($data['currency']),
            'is_active' => (bool) $data['is_active'],
            'is_public' => (bool) $data['is_public'],
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ];
    }

    private function maybeSync(Request $request, Plan $plan): void
    {
        if (! $request->boolean('sync_gateways')) {
            return;
        }
        try {
            if (PlatformSettings::stripeReady()) {
                if ($ids = PlatformPayments::syncPlan($plan)) {
                    $plan->update($ids);
                }
            }
            if (PlatformSettings::paypalReady()) {
                if ($ids = PlatformPayPal::syncPlan($plan)) {
                    $plan->update($ids);
                }
            }
        } catch (Throwable $e) {
            report($e);
            session()->flash('warning', 'Plan saved, but gateway sync failed: '.$e->getMessage());
        }
    }
}
