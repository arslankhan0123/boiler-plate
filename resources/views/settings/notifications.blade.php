@extends('layouts.admin.main')
@section('title', 'Notifications & Mail')
@section('subtitle', 'Settings')


@section('content')
<div class="app-content">
    <x-admin.page-header title="Notifications & Mail" subtitle="Your company's SMTP server, sender, and which emails go out automatically. Emails are sent only after SMTP is saved here." />

    <form method="POST" action="{{ route('settings.notifications.update') }}" class="app-form-grid" autocomplete="off">@csrf @method('PUT')
        <fieldset class="app-card app-form-card" @disabled(! $canEdit)>
            <div class="form-section-title">Sender & notifications</div>
            <div class="row g-4">
                <div class="col-md-6"><x-admin.input name="mail_from_address" type="email" label="From address" :value="$s['mail_from_address']" placeholder="support@company.com" hint="Most providers require this to match the username." /></div>
                <div class="col-md-6"><x-admin.input name="mail_from_name" label="From name" :value="$s['mail_from_name']" :placeholder="\App\Support\Settings::companyName()" /></div>
            </div>
            <div class="d-grid gap-3 mt-4">
                @foreach ($items as $key => [$title, $desc])
                    <label class="notif-row">
                        <div><div class="fw-semibold">{{ $title }}</div><div class="small text-body-secondary">{{ $desc }}</div></div>
                        <span class="form-check form-switch m-0"><input class="form-check-input" type="checkbox" role="switch" name="{{ $key }}" value="1" @checked(($s[$key] ?? '0') === '1')></span>
                    </label>
                @endforeach
            </div>

            @if ($canEdit)<div class="app-form-actions"><x-admin.button class="btn-lg-app" icon="check2">Save Settings</x-admin.button></div>@endif
        </fieldset>

        <div class="app-aside">
            <fieldset class="app-card" @disabled(! $canEdit)><div class="app-card__body">
                <h3 class="app-aside-title"><i class="bi bi-hdd-network me-1"></i> SMTP server</h3>
            
            <div class="row g-3">
                <div class="col-12">
                    <x-admin.select name="smtp_encryption" label="Encryption" :value="old('smtp_encryption', $s['smtp_encryption'] ?? 'tls')"
                        :options="['tls' => 'TLS / STARTTLS (port 587)', 'ssl' => 'SSL (port 465)', 'none' => 'None (port 25)']"
                        hint="The port decides the mode: 465 = SSL, anything else = TLS/STARTTLS." />
                </div>
                <div class="col-8"><x-admin.input name="smtp_host" label="SMTP Host" :value="$s['smtp_host']" placeholder="smtp.hostinger.com" required /></div>
                <div class="col-4"><x-admin.input name="smtp_port" type="number" label="Port" :value="$s['smtp_port']" placeholder="587" required /></div>
                <div class="col-12"><x-admin.input name="smtp_username" label="Username" :value="$s['smtp_username']" placeholder="support@company.com" autocomplete="off" /></div>
                <div class="col-12">
                    <label class="form-label" for="smtpPass">Password / App password</label>
                    <div class="position-relative">
                        <input type="password" name="smtp_password" id="smtpPass" class="form-control pe-5 @error('smtp_password') is-invalid @enderror" autocomplete="new-password"
                            placeholder="{{ $s['smtp_password'] ? '•••••••• (saved — leave blank to keep)' : 'Password' }}">
                        <button type="button" class="pass-eye" data-eye="smtpPass" aria-label="Show password"><i class="bi bi-eye"></i></button>
                    </div>
                    <div class="form-text">Stored encrypted. For Gmail, create an <b>App password</b> (Google Account → Security → 2-Step Verification → App passwords).</div>
                    @if ($s['smtp_password'])<label class="d-flex align-items-center gap-2 small mt-1"><input type="checkbox" class="form-check-input mt-0" name="clear_smtp_password" value="1"> Remove saved password</label>@endif
                </div>
                <div class="col-12">
                    <label class="verify-box">
                        <input type="checkbox" class="form-check-input mt-1" name="smtp_verify_peer" value="1" @checked(old('smtp_verify_peer', $s['smtp_verify_peer'] ?? '1') === '1')>
                        <div><div class="fw-semibold">Verify TLS certificate</div>
                            <div class="small text-body-secondary">Keep on for production. Turn off only on a local/dev machine if you get "handshake" or "certificate verify failed" errors (antivirus SSL scanning, missing CA bundle).</div></div>
                    </label>
                </div>
            </div>
                @if ($canEdit)<x-admin.button icon="check2" class="w-100 mt-3">Save SMTP</x-admin.button>@endif
            </div></fieldset>
            <div class="app-card"><div class="app-card__body">
                <h3 class="app-aside-title">Mail status</h3>
                <div class="d-flex justify-content-between small mb-2"><span class="text-body-secondary">Mailer</span><b>{{ $mailer }}</b></div>
                @if ($s['smtp_host'])
                    <div class="d-flex justify-content-between small mb-2"><span class="text-body-secondary">Server</span><b class="text-truncate ms-2">{{ $s['smtp_host'] }}:{{ $s['smtp_port'] }}</b></div>
                @endif
                <div class="d-flex justify-content-between small"><span class="text-body-secondary">Sender</span><b class="text-truncate ms-2">{{ implode(' · ', array_filter([\App\Support\Settings::fromAddress()[1], \App\Support\Settings::fromAddress()[0]])) }}</b></div>
                @if ($canEdit)
                    <button type="submit" form="testMailForm" class="btn btn-light w-100 mt-4"><i class="bi bi-send me-1"></i>Send test email</button>
                    <div class="form-text">Save first — the test goes to {{ auth()->user()->email }}.</div>
                @endif
            </div></div>
            <x-admin.tips :items="['These settings belong to your company only — every email to or from your employees uses them.', 'Hostinger / cPanel: port 465 + SSL, username = full email address.', 'Gmail: port 587 + TLS with an App Password.']" />
        </div>
    </form>
    <form id="testMailForm" method="POST" action="{{ route('settings.notifications.test') }}">@csrf</form>
</div>
@endsection

@push('styles')
<style>
    .notif-row { display: flex; justify-content: space-between; align-items: center; gap: 1rem; padding: .9rem 1rem; border: 1.5px solid var(--app-border); border-radius: 12px; cursor: pointer; margin: 0; }
    .notif-row:hover { border-color: #c7d2fe; }
    .notif-row .form-check-input { width: 2.6em; height: 1.4em; cursor: pointer; }
    .verify-box { display: flex; gap: .8rem; align-items: flex-start; padding: 1rem 1.1rem; border: 1.5px solid var(--app-border); border-radius: 12px; background: var(--app-hover); cursor: pointer; margin: 0; }
    .verify-box .form-check-input { width: 1.2em; height: 1.2em; flex-shrink: 0; }
    .pass-eye { position: absolute; right: .6rem; top: 50%; transform: translateY(-50%); border: 0; background: none; color: var(--app-text-muted); font-size: 1.15rem; padding: .25rem .4rem; }
    .pass-eye:hover { color: var(--app-primary, #4f46e5); }
</style>
@endpush

@push('scripts')
<script>
    (() => {
        const enc = document.getElementById("f_smtp_encryption"), port = document.getElementById("f_smtp_port");
        enc?.addEventListener('change', () => { if (port) port.value = { ssl: 465, tls: 587, none: 25 }[enc.value]; });
        document.querySelectorAll('[data-eye]').forEach(b => b.addEventListener('click', () => {
            const i = document.getElementById(b.dataset.eye); i.type = i.type === 'password' ? 'text' : 'password';
            b.querySelector('i').className = i.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
        }));
    })();
</script>
@endpush
