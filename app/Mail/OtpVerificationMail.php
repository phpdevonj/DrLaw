<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OtpVerificationMail extends Mailable
{
    use SerializesModels;

    /** @var string */
    public $otp;

    /**
     * Create a new message instance.
     */
    public function __construct(string $otp)
    {
        $this->otp = $otp;
    }

    /**
     * Build the message.
     */
    public function build(): self
    {
        return $this
            ->subject('OTP Verification – ' . config('app.name'))
            ->view('emails.otp_verification');
    }
}
