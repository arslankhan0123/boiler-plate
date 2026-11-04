@extends('layouts.admin.main')
@section('title', 'Storefront')
@section('subtitle', 'E-commerce')
@section('content')
<div class="app-content">
    <x-admin.page-header title="Storefront & Payments" subtitle="Your public online store settings" />
    <form method="POST" action="{{ route('ecommerce.storefront.update') }}">@csrf @method('PUT')
    <div class="row g-4">
        <div class="col-lg-8">
            <x-admin.card title="Store">
                <div class="row g-3">
                    <div class="col-md-6"><x-admin.input name="store_name" label="Store name" :value="$store['store_name']" required /></div>
                    <div class="col-md-6"><x-admin.input name="store_tagline" label="Tagline" :value="$store['store_tagline']" optional /></div>
                    <div class="col-md-6"><x-admin.input name="store_free_shipping_over" label="Free shipping over (amount)" :value="$store['store_free_shipping_over']" optional hint="Leave blank to disable the free-shipping bar." /></div>
                </div>
                <label class="form-check mt-3"><input type="checkbox" name="store_enabled" value="1" class="form-check-input" @checked($store['store_enabled'] === '1')> <span class="form-check-label">Store is open (publicly accessible)</span></label>
            </x-admin.card>

            <x-admin.card title="Store appearance" subtitle="Pick a colour theme and layout for your public store." class="mt-4">
                @php
                    $curTheme = old('store_theme', $store['store_theme'] ?? 'emerald');
                    $curLayout = old('store_layout', $store['store_layout'] ?? 'classic');
                    $curPrimary = old('store_primary', $store['store_primary'] ?? '');
                @endphp
                <label class="form-label">Theme colour</label>
                <div class="theme-grid mb-4">
                    @foreach ($themes as $key => $t)
                        <label class="theme-opt">
                            <input type="radio" name="store_theme" value="{{ $key }}" @checked($curTheme === $key)>
                            <span class="theme-card">
                                <span class="theme-swatch" style="background:linear-gradient(135deg,{{ $t['brand'] }} 0%,{{ $t['brand'] }} 60%,{{ $t['accent'] }} 100%)"></span>
                                <span class="theme-name">{{ $t['label'] }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>

                <div class="app-field mb-4">
                    <label class="form-label">Custom brand colour <span class="opt">(optional — overrides the theme colour)</span></label>
                    <div class="d-flex gap-2 align-items-center flex-wrap">
                        <input type="color" id="primaryPick" value="{{ $curPrimary ?: '#0f766e' }}" class="form-control form-control-color" style="width:56px;height:42px;padding:4px">
                        <input type="text" name="store_primary" id="primaryHex" class="form-control" placeholder="#0f766e" value="{{ $curPrimary }}" maxlength="7" style="max-width:160px">
                        <button type="button" class="btn btn-light btn-sm" id="primaryClear">Use theme colour</button>
                    </div>
                    <div class="form-text">Leave blank to use the selected theme's own colour. Shades and gradients are generated automatically.</div>
                </div>

                <label class="form-label">Layout style</label>
                <div class="row g-2">
                    @foreach ($layouts as $key => $l)
                        <div class="col-md-4">
                            <label class="layout-opt">
                                <input type="radio" name="store_layout" value="{{ $key }}" @checked($curLayout === $key)>
                                <span class="layout-card">
                                    <span class="layout-name">{{ $l['label'] }}</span>
                                    <span class="layout-desc">{{ $l['desc'] }}</span>
                                </span>
                            </label>
                        </div>
                    @endforeach
                </div>
                <div class="form-text mt-2"><i class="bi bi-eye me-1"></i>Save, then open your store link to preview the new look.</div>
            </x-admin.card>

            <x-admin.card title="Payment methods" class="mt-4">
                <label class="form-check"><input type="checkbox" name="store_cod" value="1" class="form-check-input" @checked($store['store_cod'] === '1')> <span class="form-check-label">Cash on Delivery</span></label>
                <label class="form-check"><input type="checkbox" name="store_bank_transfer" value="1" class="form-check-input" @checked($store['store_bank_transfer'] === '1')> <span class="form-check-label">Bank Transfer</span></label>
                <div class="app-field mt-2">
                    <label for="f_bankd" class="form-label">Bank details (shown at checkout) <span class="opt">(optional)</span></label>
                    <textarea id="f_bankd" name="store_bank_details" rows="3" class="form-control" placeholder="Bank name, account title, account number / IBAN…">{{ old('store_bank_details', $store['store_bank_details']) }}</textarea>
                </div>

                <hr class="my-4">
                <h3 class="app-aside-title d-flex align-items-center gap-2"><i class="bi bi-credit-card-2-front"></i> Stripe (Credit / Debit Card)</h3>
                <label class="form-check mb-2"><input type="checkbox" name="pay_stripe_enabled" value="1" class="form-check-input" @checked($store['pay_stripe_enabled'] === '1')> <span class="form-check-label">Enable card payments via Stripe</span></label>
                <div class="row g-3">
                    <div class="col-md-6"><x-admin.input name="stripe_publishable_key" label="Publishable key" :value="$store['stripe_publishable_key']" optional placeholder="pk_live_… or pk_test_…" /></div>
                    <div class="col-md-6"><x-admin.input type="password" name="stripe_secret_key" label="Secret key" :value="''" optional :placeholder="$store['stripe_secret_set'] ? '•••••••• (saved — type to change)' : 'sk_live_… or sk_test_…'" /></div>
                    <div class="col-md-6"><x-admin.input type="password" name="stripe_webhook_secret" label="Webhook signing secret" :value="''" optional :placeholder="$store['stripe_webhook_set'] ? '•••••••• (saved — type to change)' : 'whsec_…'" /></div>
                    <div class="col-12">
                        <label class="form-label small text-body-secondary mb-1">Webhook endpoint URL (add this in Stripe → Developers → Webhooks)</label>
                        <div class="input-group">
                            <input type="text" readonly class="form-control" id="whUrl" value="{{ url('/store/'.$tenantId.'/webhook/stripe') }}">
                            <button class="btn btn-light" type="button" onclick="navigator.clipboard.writeText(document.getElementById('whUrl').value)"><i class="bi bi-clipboard"></i></button>
                        </div>
                        <small class="text-body-secondary">Listen for <code>payment_intent.succeeded</code> and <code>payment_intent.canceled</code>.</small>
                    </div>
                </div>

                <hr class="my-4">
                <h3 class="app-aside-title d-flex align-items-center gap-2"><i class="bi bi-paypal"></i> PayPal</h3>
                <label class="form-check mb-2"><input type="checkbox" name="pay_paypal_enabled" value="1" class="form-check-input" @checked($store['pay_paypal_enabled'] === '1')> <span class="form-check-label">Enable PayPal</span></label>
                <div class="row g-3">
                    <div class="col-md-6"><x-admin.input name="paypal_client_id" label="Client ID" :value="$store['paypal_client_id']" optional /></div>
                    <div class="col-md-6"><x-admin.input type="password" name="paypal_secret" label="Secret" :value="''" optional :placeholder="$store['paypal_secret_set'] ? '•••••••• (saved — type to change)' : 'PayPal app secret'" /></div>
                    <div class="col-md-6">
                        <label class="form-label">Mode</label>
                        <select name="paypal_mode" class="form-select">
                            <option value="sandbox" @selected($store['paypal_mode']==='sandbox')>Sandbox (testing)</option>
                            <option value="live" @selected($store['paypal_mode']==='live')>Live</option>
                        </select>
                    </div>
                </div>

                <div class="mt-4"><x-admin.button icon="check-lg">Save settings</x-admin.button></div>
            </x-admin.card>
        </div>

        <div class="col-lg-4"><x-admin.card title="Your store link">
            <p class="small text-body-secondary">Share this public link with customers:</p>
            <div class="input-group">
                <input type="text" readonly class="form-control" value="{{ url('/store/'.$tenantId) }}" id="storeUrl">
                <button class="btn btn-light" type="button" onclick="navigator.clipboard.writeText(document.getElementById('storeUrl').value)"><i class="bi bi-clipboard"></i></button>
            </div>
            <a href="{{ url('/store/'.$tenantId) }}" target="_blank" class="btn btn-primary w-100 mt-2"><i class="bi bi-box-arrow-up-right me-1"></i>Open store</a>
            <hr>
            <p class="small text-body-secondary mb-1"><i class="bi bi-shield-lock me-1"></i>Secret keys are stored encrypted and never shown again.</p>
            <p class="small text-body-secondary mb-0">A method only appears at checkout once it's enabled <em>and</em> its keys are filled in.</p>
        </x-admin.card></div>
    </div>
    </form>

    {{-- ───────────────────────── Custom domain ───────────────────────── --}}
    <div class="row g-4 mt-1">
        <div class="col-lg-8">
            <x-admin.card title="Your own domain">
                <p class="text-body-secondary small mb-3">Run your store on your <strong>own domain</strong> (e.g. <code>shop.yourbrand.com</code> or <code>yourbrand.com</code>) while everything is still managed here. No separate hosting or app needed.</p>

                @if ($domains->isEmpty())
                    <div class="text-body-secondary small mb-3"><i class="bi bi-globe2 me-1"></i>No custom domain connected yet.</div>
                @else
                    <table class="table app-table align-middle mb-3">
                        <thead><tr><th>Domain</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                        <tbody>
                        @foreach ($domains as $d)
                            <tr>
                                <td><a href="https://{{ $d->domain }}" target="_blank" rel="noopener">{{ $d->domain }}</a></td>
                                <td>
                                    @if ($d->verified_at)
                                        <span class="badge text-bg-success"><i class="bi bi-check-circle me-1"></i>Verified</span>
                                    @else
                                        <span class="badge text-bg-warning"><i class="bi bi-hourglass-split me-1"></i>Pending verification</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @unless ($d->verified_at)
                                        <form method="POST" action="{{ route('ecommerce.storefront.domains.verify', $d->id) }}" class="d-inline">@csrf
                                            <button class="btn btn-sm btn-primary"><i class="bi bi-arrow-repeat me-1"></i>Verify</button>
                                        </form>
                                    @endunless
                                    <form method="POST" action="{{ route('ecommerce.storefront.domains.destroy', $d->id) }}" class="d-inline" onsubmit="return confirm('Remove this domain?')">@csrf @method('DELETE')
                                        <button class="btn btn-sm btn-light text-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            @unless ($d->verified_at)
                            <tr><td colspan="3" class="bg-body-tertiary">
                                <div class="small">
                                    <div class="fw-semibold mb-2"><i class="bi bi-1-circle me-1"></i>Add these DNS records at your domain provider:</div>
                                    <div class="mb-2"><span class="text-body-secondary">a) Ownership (TXT record)</span>
                                        <div class="font-monospace mt-1" style="word-break:break-all">
                                            Type: <b>TXT</b> &nbsp;·&nbsp; Host/Name: <b>_store-verify.{{ $d->domain }}</b><br>
                                            Value: <b>{{ $d->token }}</b>
                                        </div>
                                    </div>
                                    <div class="mb-1"><span class="text-body-secondary">b) Point the domain to us</span>
                                        <div class="font-monospace mt-1" style="word-break:break-all">
                                            Sub-domain (e.g. shop.yourbrand.com): <b>CNAME → {{ $platformHost }}</b><br>
                                            Root domain (yourbrand.com): <b>A record → your platform server's IP</b>
                                        </div>
                                    </div>
                                    <div class="text-body-secondary mt-2"><i class="bi bi-2-circle me-1"></i>Then click <b>Verify</b>. DNS changes can take a few minutes (sometimes up to an hour) to go live.</div>
                                </div>
                            </td></tr>
                            @endunless
                        @endforeach
                        </tbody>
                    </table>
                @endif

                <form method="POST" action="{{ route('ecommerce.storefront.domains.store') }}" class="row g-2 align-items-end">@csrf
                    <div class="col-sm-8"><x-admin.input name="domain" label="Add a custom domain" placeholder="shop.yourbrand.com" /></div>
                    <div class="col-sm-4"><button class="btn btn-primary w-100"><i class="bi bi-plus-lg me-1"></i>Add domain</button></div>
                </form>
            </x-admin.card>
        </div>

        <div class="col-lg-4">
            <x-admin.card title="How it works">
                <ol class="small ps-3 mb-0" style="line-height:1.9">
                    <li>Type your domain above and click <b>Add domain</b>.</li>
                    <li>Copy the two DNS records we show and add them at your domain provider (GoDaddy, Namecheap, Cloudflare, etc.).</li>
                    <li>The <b>TXT</b> record proves the domain is yours.</li>
                    <li>The <b>CNAME</b> (for a sub-domain) or <b>A record</b> (for a root domain) points visitors to this platform.</li>
                    <li>Click <b>Verify</b>. Once it turns <b>Verified</b> and DNS has propagated, your store opens on your domain.</li>
                </ol>
                <hr>
                <p class="small text-body-secondary mb-0"><i class="bi bi-shield-lock me-1"></i>HTTPS/SSL for your domain is handled by the platform server. You only manage DNS; everything else stays here.</p>
            </x-admin.card>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .theme-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(120px,1fr)); gap:.6rem; }
    .theme-opt input, .layout-opt input { position:absolute; opacity:0; width:0; height:0; }
    .theme-card { display:flex; flex-direction:column; align-items:center; gap:.45rem; padding:.6rem .5rem .55rem; border:1.5px solid var(--app-border); border-radius:12px; cursor:pointer; transition:all .15s; background:var(--app-surface); }
    .theme-swatch { width:100%; height:44px; border-radius:9px; box-shadow:inset 0 0 0 1px rgba(0,0,0,.06); }
    .theme-name { font-size:.8rem; font-weight:600; color:var(--app-text); }
    .theme-opt input:checked + .theme-card { border-color:var(--app-primary,#4f46e5); box-shadow:0 0 0 3px rgba(99,102,241,.18); }
    .theme-opt:hover .theme-card { border-color:#a5b4fc; }
    .layout-card { display:block; padding:.75rem .9rem; border:1.5px solid var(--app-border); border-radius:12px; cursor:pointer; transition:all .15s; background:var(--app-surface); height:100%; }
    .layout-name { display:block; font-weight:700; color:var(--app-text); font-size:.9rem; }
    .layout-desc { display:block; font-size:.78rem; color:var(--app-text-muted); margin-top:.15rem; }
    .layout-opt input:checked + .layout-card { border-color:var(--app-primary,#4f46e5); background:rgba(99,102,241,.05); box-shadow:0 0 0 3px rgba(99,102,241,.15); }
</style>
@endpush

@push('scripts')
<script>
(() => {
    const pick = document.getElementById('primaryPick'), hex = document.getElementById('primaryHex'), clear = document.getElementById('primaryClear');
    if (!pick || !hex) return;
    pick.addEventListener('input', () => { hex.value = pick.value; });
    hex.addEventListener('input', () => { if (/^#[0-9a-fA-F]{6}$/.test(hex.value)) pick.value = hex.value; });
    clear?.addEventListener('click', () => { hex.value = ''; pick.value = '#0f766e'; });
})();
</script>
@endpush
