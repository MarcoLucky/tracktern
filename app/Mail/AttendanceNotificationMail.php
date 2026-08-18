<?php

namespace App\Mail;

use App\Models\Attendance;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AttendanceNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public Attendance $attendance;
    public string $actionType;

    public function __construct(Attendance $attendance, string $actionType)
    {
        $this->attendance = $attendance;
        $this->actionType = $actionType;
    }

    public function build()
    {
        $subject = $this->actionType === 'time-in'
            ? 'TrackTern Notification: DTR Time In Recorded'
            : 'TrackTern Notification: DTR Time Out Recorded';

        return $this->subject($subject)
            ->html("
                <div style='font-family: Arial, sans-serif; padding: 20px; color: #1E1E1E;'>
                    <h2 style='color: #004798;'>TrackTern DTR Notification</h2>
                    <p>Hello <strong>{$this->attendance->student->user->name}</strong>,</p>
                    <p>Your DTR <strong>" . strtoupper($this->actionType) . "</strong> has been recorded successfully with server-verified timestamp.</p>
                    <ul>
                        <li><strong>Student Code / ID:</strong> {$this->attendance->student->intern_id}</li>
                        <li><strong>Date:</strong> {$this->attendance->date->format('Y-m-d')}</li>
                        <li><strong>Timestamp:</strong> " . ($this->actionType === 'time-in' ? $this->attendance->time_in->format('Y-m-d h:i:s A') : $this->attendance->time_out->format('Y-m-d h:i:s A')) . "</li>
                        " . ($this->actionType === 'time-out' ? "<li><strong>Rendered Time:</strong> " . number_format($this->attendance->rendered_hours, 2) . " hours</li>" : "") . "
                    </ul>
                    <p>Thank you for keeping your internship time records up to date.</p>
                    <hr style='border: none; border-top: 1px solid #E5E7EB;'>
                    <p style='font-size: 12px; color: #6B7280;'>TrackTern Student Internship Monitoring System</p>
                </div>
            ");
    }
}
