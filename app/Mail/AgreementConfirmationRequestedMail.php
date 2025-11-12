<?php

namespace App\Mail;

use App\Models\Practice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AgreementConfirmationRequestedMail extends Mailable
{
    use Queueable, SerializesModels;

    public Practice $practice;
    public array $options;

    public function __construct(Practice $practice, array $options= [])
    {
        $this->practice = $practice;
        $this->options = $options;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('mail.agreement_confirmation_requested'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.agreement-confirmation-requested',
            with: [
                'practice' => $this->practice,
                'options' => $this->options,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
