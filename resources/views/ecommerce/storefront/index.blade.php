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
