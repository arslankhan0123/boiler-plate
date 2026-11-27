<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Support\Settings;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** One generic email used by every module (leave, payslip, task, interview…). */
class AppNotice extends Notification
{
    public function __construct(
        public string $subject,
        public array $lines,
        public ?string $actionText = null,
        public ?string $actionUrl = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $company = Settings::companyName();
        $mail = (new MailMessage())
            ->subject($this->subject.' — '.$company)
            ->greeting('Hello '.strtok((string) ($notifiable->name ?? ''), ' ').',');

        [$address, $name] = Settings::fromAddress();
        $mail->from($address, $name)->mailer(Settings::mailer() ?? throw new \RuntimeException('SMTP is not configured for this company.'));
        foreach ($this->lines as $line) {
            $mail->line($line);
        }
        if ($this->actionText && $this->actionUrl) {
            $mail->action($this->actionText, $this->actionUrl);
        }

        return $mail->salutation('— '.$company);
    }
}
