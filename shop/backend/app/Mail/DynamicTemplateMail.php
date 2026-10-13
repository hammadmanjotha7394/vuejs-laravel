<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DynamicTemplateMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $subjectLine,
        public string $renderedHtml,
        public string $templateKey = 'custom',
        public ?string $replyToAddress = null,
    ) {}

    public function envelope(): Envelope
    {
        $replyTo = $this->replyToAddress ? [new Address($this->replyToAddress)] : [];

        return new Envelope(
            subject: $this->subjectLine,
            replyTo: $replyTo,
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: $this->renderedHtml,
        );
    }
}
