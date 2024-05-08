<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class sendEmailOnCommandFailed extends Mailable
{
    use Queueable, SerializesModels;

    protected object $cronSchedule;
    protected string $subjectText;
    protected string $errorMessage;

    public function __construct($cronSchedule, $subject, $errorMessage = '')
    {
        $this->cronSchedule = $cronSchedule;
        $this->subjectText = $subject;
        $this->errorMessage = $errorMessage ? $errorMessage : '';
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectText . ' - ' . $this->cronSchedule->command_name,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emailTemplates.command_failed',
            with: ['subject' => $this->subjectText, 'command_name' => $this->cronSchedule->command_name, 'error_message' => $this->errorMessage]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
