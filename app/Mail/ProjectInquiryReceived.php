<?php

namespace App\Mail;

use App\Models\ProjectInquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProjectInquiryReceived extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ProjectInquiry $inquiry,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New project inquiry — '.($this->inquiry->name ?: 'New client'),
            to: [
                new Address(config('mail.inquiries_to', 'hello@webxpresstech.com.ng')),
            ],
            replyTo: [
                new Address($this->inquiry->email),
            ],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.inquiry-received',
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
