<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ValidationMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $validationCode;

    public function __construct(string $validationCode)
    {
        $this->validationCode = $validationCode;
    }

    public function build()
    {
        return $this->subject('Validate your email')->view('mail.validation-mail');
    }
}
