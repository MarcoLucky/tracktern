<?php

namespace App\Mail;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TaskNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public Task $task;
    public string $actionType;

    public function __construct(Task $task, string $actionType)
    {
        $this->task = $task;
        $this->actionType = $actionType;
    }

    public function build()
    {
        $statusText = match ($this->actionType) {
            'approve' => 'Approved',
            'reject' => 'Rejected',
            default => 'Revision Requested',
        };
        $subject = "TrackTern Notification: Task Log {$statusText} - {$this->task->title}";
        $statusColor = $this->actionType === 'approve' ? '#007A33' : '#DC2626';

        return $this->subject($subject)
            ->html("
                <div style='font-family: Arial, sans-serif; padding: 20px; color: #1E1E1E;'>
                    <h2 style='color: #004798;'>TrackTern Task Submission Update</h2>
                    <p>Hello <strong>{$this->task->student->user->name}</strong>,</p>
                    <p>Your submitted task log <strong>\"{$this->task->title}\"</strong> has been reviewed by your instructor.</p>
                    <ul>
                        <li><strong>Status:</strong> <span style='color: {$statusColor}; font-weight: bold;'>{$statusText}</span></li>
                        <li><strong>Category:</strong> {$this->task->category}</li>
                        <li><strong>Submitted Date:</strong> {$this->task->submitted_at->format('Y-m-d')}</li>
                        <li><strong>Teacher Feedback:</strong> " . ($this->task->teacher_feedback ?: 'None provided') . "</li>
                    </ul>
                    <hr style='border: none; border-top: 1px solid #E5E7EB;'>
                    <p style='font-size: 12px; color: #6B7280;'>TrackTern Student Internship Monitoring System</p>
                </div>
            ");
    }
}
