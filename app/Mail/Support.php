<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class Support extends Mailable
{
    use Queueable, SerializesModels;

    public $files;
    public string $userUUID;
    public string $title;
    public string $category;
    public string $ticketId;
    public string $description;
    public string $github_email;
    public string $google_email;
    public string $cellphone_number;

    /**
     * Create a new message instance.
     */
    public function __construct(
        $files,
        string $userUUID,
        string $title,
        string $category,
        string $ticketId,
        string $description,
        string $github_email,
        string $google_email,
        string $cellphone_number,
    ) {
        $this->files = $files;
        $this->userUUID = $userUUID;
        $this->title = $title;
        $this->category = $category;
        $this->ticketId = $ticketId;
        $this->description = $description;
        $this->github_email = $github_email;
        $this->google_email = $google_email;
        $this->cellphone_number = $cellphone_number;
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        $attachments = [];

        foreach ($this->files as $file) {
            $attachments[] = Attachment::fromPath($file->getRealPath())
                ->as($file->getClientOriginalName())
                ->withMime($file->getMimeType());
        }

        return $attachments;
    }

    public function build()
    {
        return $this->subject("Planossauro - support")->view("mail.support");
    }
}
