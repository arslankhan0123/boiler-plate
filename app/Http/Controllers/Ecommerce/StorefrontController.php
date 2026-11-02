<?php

declare(strict_types=1);

namespace App\Http\Controllers\Ecommerce;

use App\Support\Settings;
use Illuminate\Http\Request;

/** Storefront & payment settings. */
class StorefrontController extends EcommerceController
{
    public function index()
    {
        $this->need('ecommerce.settings');

        return view('ecommerce.storefront.index', [
            'store' => [
                'store_enabled' => Settings::get('store_enabled', '1'),
                'store_name' => Settings::get('store_name', Settings::get('company_name', 'My Store')),
                'store_tagline' => Settings::get('store_tagline', ''),
                'store_cod' => Settings::get('store_cod', '1'),
                'store_bank_transfer' => Settings::get('store_bank_transfer', '0'),
            ],
            'tenantId' => auth()->user()->tenant_id,
        ]);
    }

    public function update(Request $request)
    {
        $this->need('ecommerce.settings');
        $data = $request->validate([
            'store_name' => ['required', 'string', 'max:120'],
            'store_tagline' => ['nullable', 'string', 'max:200'],
        ]);
        Settings::set($data + [
            'store_enabled' => $request->boolean('store_enabled') ? '1' : '0',
            'store_cod' => $request->boolean('store_cod') ? '1' : '0',
            'store_bank_transfer' => $request->boolean('store_bank_transfer') ? '1' : '0',
        ]);

        return back()->with('success', 'Store settings saved.');
    }
}
