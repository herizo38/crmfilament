<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WeeklyReportMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<string, mixed>  $rapport
     */
    public function __construct(public array $rapport) {}

    public function envelope(): Envelope
    {
        $prenom = $this->rapport['user']->prenom ?? '';

        return new Envelope(
            subject: 'Votre rapport hebdomadaire CRM'.($prenom ? " — {$prenom}" : ''),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.weekly-report',
            with: ['rapport' => $this->rapport],
        );
    }
}
