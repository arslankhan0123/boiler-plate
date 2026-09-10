<x-mail::message>
# Password reset request

Use the following One-Time Password (OTP) to reset your password:

<x-mail::panel>
{{ $otp }}
</x-mail::panel>

This OTP is valid for **{{ $expiryMinutes }} minutes**.

If you did not request a password reset, no action is required.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
