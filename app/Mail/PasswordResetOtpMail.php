<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PasswordResetOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public string $otp;

    public function __construct(User $user, string $otp)
    {
        $this->user = $user;
        $this->otp = $otp;
    }

    public function build()
    {
        return $this->subject('TrackTern Password Reset OTP')
            ->html("
                <div style='font-family: Arial, sans-serif; padding: 20px; color: #1E1E1E;'>
                    <h2 style='color: #004798;'>TrackTern Password Reset</h2>
                    <p>Hello <strong>" . e($this->user->name) . "</strong>,</p>
                    <p>Use the OTP code below to verify your password reset request.</p>
                    <div style='font-size: 30px; letter-spacing: 8px; font-weight: 800; color: #004798; padding: 14px 0;'>
                        " . e($this->otp) . "
                    </div>
                    <p>This code expires in 15 minutes. If you did not request this reset, you can ignore this email.</p>
                    <hr style='border: none; border-top: 1px solid #E5E7EB;'>
                    <p style='font-size: 12px; color: #6B7280;'>TrackTern Student Internship Monitoring System</p>
                </div>
            ");
    }
}
