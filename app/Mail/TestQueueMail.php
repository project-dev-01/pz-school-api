<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TestQueueMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    // public array $content;
    public $loginId;
    public $password;
    /**
     * Create a new message instance.
     */
    public function __construct($loginId, $password)
    {
        //
        // $this->content = $content;
        $this->loginId = $loginId;
        $this->password = $password;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: env('MAIL_FROM_ADDRESS', config('constants.client_email')),
            subject: '【Suzen】アカウント情報のご案内',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'auth.testqueue',
        );
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
