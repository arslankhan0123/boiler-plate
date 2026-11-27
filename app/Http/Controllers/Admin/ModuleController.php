<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Support\ModuleAccess;
use App\Support\ModuleRegistry;
use Illuminate\Support\Facades\DB;

/**
 * Generic module + feature screens. Each request passes BOTH checks:
 * module enabled for the tenant, and the user's permission.
 */
class ModuleController extends Controller
{
    /** Platform: the module registry with dependencies. */
    public function index()
    {
        abort_unless(auth()->user()->can('modules.view'), 403);
        ModuleRegistry::sync();

        $usage = DB::connection(config('tenancy.database.central_connection'))
            ->table('tenant_modules')->selectRaw('module_code, COUNT(*) as c')->groupBy('module_code')->pluck('c', 'module_code');

        return view('admin.modules.index', [
            'modules' => ModuleRegistry::all(),
            'groups' => ModuleRegistry::groups(),
            'usage' => $usage,
            'tenantCount' => Tenant::query()->count(),
        ]);
    }

    /** Module overview: the features this user can open. */
    public function module(string $module)
    {
        $user = auth()->user();
        $visible = ModuleAccess::visibleModules($user);
        abort_unless(ModuleRegistry::exists($module), 404);
        abort_unless(isset($visible[$module]), 403, 'This module is not enabled for you.');

        $m = $visible[$module];
        if ($m['route']) {
            return redirect()->route($m['route']);
        }

        return view('admin.modules.show', ['code' => $module, 'module' => $m]);
    }

    /** One feature (submodule) page. */
    public function feature(string $module, string $feature)
    {
        $user = auth()->user();
        $m = ModuleRegistry::get($module);
        abort_unless($m && isset($m['features'][$feature]), 404);
        abort_unless(ModuleAccess::featureAvailable($user, $module, $feature), 403, 'This feature is not enabled for your organization.');
        abort_unless($user->can($m['features'][$feature]['permission']), 403);

        return view('admin.modules.feature', [
            'code' => $module,
            'module' => $m,
            'featureCode' => $feature,
            'feature' => $m['features'][$feature],
            'siblings' => ModuleAccess::visibleModules($user)[$module]['visible_features'] ?? [],
        ]);
    }
}
