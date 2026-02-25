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

    /**
     * Create a new message instance.
     */
    public function __construct($files, string $userUUID, string $title, string $category, string $ticketId, string $description)
    {
        $this->files = $files;
        $this->userUUID = $userUUID;
        $this->title = $title;
        $this->category = $category;
        $this->ticketId = $ticketId;
        $this->description = $description;
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
        return $this->subject('Planeja.AI - support')->view('mail.support');
    }
}
