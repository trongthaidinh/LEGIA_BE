<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ResetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public $token;
    public $email;

    public function __construct($token, $email)
    {
        $this->token = $token;
        $this->email = $email;
    }

    public function build()
    {
        $url = url('http://localhost:2003/en/reset-password?token=' . $this->token . '&email=' . $this->email);

        return $this->subject('Password Reset Request')
                    ->view('emails.reset_password')
                    ->with([
                        'url' => $url,
                    ]);
    }
}
