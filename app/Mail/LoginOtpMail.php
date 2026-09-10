<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Config;

class LoginOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public readonly int $expiryMinutes;

    public function __construct(public readonly string $otp)
    {
        $this->expiryMinutes = Config::integer('app.otp_expiry_minutes');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your login verification code',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.login-otp',
            with: [
                'otp' => $this->otp,
                'expiryMinutes' => $this->expiryMinutes,
            ],
        );
    }
}
