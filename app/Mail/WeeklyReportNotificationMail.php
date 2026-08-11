<?php

namespace App\Mail;

use App\Models\WeeklyReport;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WeeklyReportNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public WeeklyReport $report;
    public string $actionType;

    public function __construct(WeeklyReport $report, string $actionType)
    {
        $this->report = $report;
        $this->actionType = $actionType;
    }

    public function build()
    {
        $statusText = $this->actionType === 'approve' ? 'Approved' : 'Revision Requested';
        $subject = "TrackTern Notification: Weekly Report #{$this->report->week_number} {$statusText}";

        return $this->subject($subject)
            ->html("
                <div style='font-family: Arial, sans-serif; padding: 20px; color: #1E1E1E;'>
                    <h2 style='color: #004798;'>TrackTern Weekly Report Update</h2>
                    <p>Hello <strong>{$this->report->student->user->name}</strong>,</p>
                    <p>Your <strong>Week #{$this->report->week_number} Progress Report</strong> has been reviewed by your instructor.</p>
                    <ul>
                        <li><strong>Status:</strong> <span style='color: " . ($this->actionType === 'approve' ? '#007A33' : '#DC2626') . "; font-weight: bold;'>{$statusText}</span></li>
                        <li><strong>Coverage Period:</strong> {$this->report->coverage_start_date->format('Y-m-d')} to {$this->report->coverage_end_date->format('Y-m-d')}</li>
                        <li><strong>Teacher Feedback:</strong> " . ($this->report->teacher_feedback ?: 'None provided') . "</li>
                    </ul>
                    <hr style='border: none; border-top: 1px solid #E5E7EB;'>
                    <p style='font-size: 12px; color: #6B7280;'>TrackTern Student Internship Monitoring System</p>
                </div>
            ");
    }
}
