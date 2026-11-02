<?php

declare(strict_types=1);

namespace App\Http\Controllers\Retail;

use App\Models\Retail\Document;
use App\Support\Settings;
use Illuminate\Http\Request;

/** Sales & purchase settings: number prefixes, round off, invoice terms / footer and print options. */
class RetailSettingsController extends RetailController
{
    public const KEYS = ['round_off', 'invoice_terms', 'invoice_footer', 'show_previous_due', 'show_tax_column', 'thermal_width', 'invoice_title', 'prefix_receipt', 'prefix_voucher'];

    public function edit()
    {
        $this->need('sales.settings');

        return view('retail.settings', ['s' => Settings::all(), 'types' => collect(Document::TYPES)->except('hold')]);
    }

    public function update(Request $request)
    {
        $this->need('sales.settings');
        $rules = [
            'invoice_terms' => ['nullable', 'string', 'max:3000'], 'invoice_footer' => ['nullable', 'string', 'max:1000'], 'invoice_title' => ['nullable', 'string', 'max:60'],
            'thermal_width' => ['required', 'in:58,80'], 'prefix_receipt' => ['nullable', 'alpha_dash', 'max:10'], 'prefix_voucher' => ['nullable', 'alpha_dash', 'max:10'],
        ];
        foreach (array_keys(Document::TYPES) as $t) {
            $rules['prefix_'.$t] = ['nullable', 'alpha_dash', 'max:10'];
        }
        $data = $request->validate($rules);
        foreach (['round_off', 'show_previous_due', 'show_tax_column'] as $b) {
            $data[$b] = $request->boolean($b) ? '1' : '0';
        }
        Settings::set($data);

        return back()->with('success', 'Sales settings saved.');
    }
}
