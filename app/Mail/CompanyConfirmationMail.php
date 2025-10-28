<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CompanyConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    protected $company;

    /**
     * Create a new message instance.
     */
    public function __construct($company)
    {
        $this->company = $company;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Aktivácia účtu spoločnosti',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: "emails.company_confirmation",
        );
    }

    public function build(): CompanyConfirmationMail
    {
//        $activationUrl = url('/api/company/activate/' . $this->company->activation_token);
        $frontendUrl = config('app.frontend_url', 'http://localhost:3000');
        $activationUrl = $frontendUrl . '/company-activation?token=' . $this->company->activation_token;
        return $this->view('emails.company_confirmation')
            ->with([
                'company' => $this->company,
                'activationUrl' => $activationUrl,
            ]);
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
