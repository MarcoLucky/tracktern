<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Task;
use App\Models\User;
use App\Models\WeeklyReport;
use Illuminate\Support\Facades\Log;
use PHPMailer\PHPMailer\PHPMailer;

class PhpMailerService
{
    public function sendPasswordResetOtp(User $user, string $otp): bool
    {
        $safeName = e($user->name);
        $safeOtp = e($otp);

        return $this->send(
            toEmail: $user->email,
            toName: $user->name,
            subject: 'TrackTern Password Reset OTP',
            htmlBody: "
                <div style='font-family: Arial, sans-serif; padding: 20px; color: #1E1E1E;'>
                    <h2 style='color: #004798;'>TrackTern Password Reset</h2>
                    <p>Hello <strong>{$safeName}</strong>,</p>
                    <p>Use the OTP code below to verify your password reset request.</p>
                    <div style='font-size: 30px; letter-spacing: 8px; font-weight: 800; color: #004798; padding: 14px 0;'>{$safeOtp}</div>
                    <p>This code expires in 15 minutes. If you did not request this reset, you can ignore this email.</p>
                    <hr style='border: none; border-top: 1px solid #E5E7EB;'>
                    <p style='font-size: 12px; color: #6B7280;'>TrackTern Student Internship Monitoring System</p>
                </div>
            ",
            textBody: "Your TrackTern password reset OTP is {$otp}. It expires in 15 minutes."
        );
    }

    public function sendTaskNotification(Task $task, string $actionType): bool
    {
        $task->loadMissing('student.user');

        if (!$task->student?->user?->email) {
            return false;
        }

        $statusText = match ($actionType) {
            'submitted' => 'Submitted',
            'approve' => 'Approved',
            'reject' => 'Rejected',
            default => 'Revision Requested',
        };
        $statusColor = in_array($actionType, ['approve', 'submitted'], true) ? '#007A33' : '#DC2626';
        $subject = "TrackTern Notification: Task Log {$statusText} - {$task->title}";
        $feedback = $task->teacher_feedback ?: 'None provided';

        return $this->send(
            toEmail: $task->student->user->email,
            toName: $task->student->user->name,
            subject: $subject,
            htmlBody: "
                <div style='font-family: Arial, sans-serif; padding: 20px; color: #1E1E1E;'>
                    <h2 style='color: #004798;'>TrackTern Task Submission Update</h2>
                    <p>Hello <strong>" . e($task->student->user->name) . "</strong>,</p>
                    <p>Your task log <strong>\"" . e($task->title) . "\"</strong> has been {$statusText}.</p>
                    <ul>
                        <li><strong>Status:</strong> <span style='color: {$statusColor}; font-weight: bold;'>{$statusText}</span></li>
                        <li><strong>Category:</strong> " . e($task->category) . "</li>
                        <li><strong>Submitted Date:</strong> " . optional($task->submitted_at)->format('Y-m-d') . "</li>
                        <li><strong>Teacher Feedback:</strong> " . e($feedback) . "</li>
                    </ul>
                    <hr style='border: none; border-top: 1px solid #E5E7EB;'>
                    <p style='font-size: 12px; color: #6B7280;'>TrackTern Student Internship Monitoring System</p>
                </div>
            ",
            textBody: "Your TrackTern task log \"{$task->title}\" is {$statusText}. Feedback: {$feedback}"
        );
    }

    public function sendTaskSubmissionToTeacher(Task $task): bool
    {
        $task->loadMissing(['student.user', 'classroom.teacher.user', 'attendance']);

        if (!$task->classroom?->teacher?->user?->email) {
            return false;
        }

        $studentName = $task->student?->user?->name ?: 'Student';
        $dtrDate = $task->attendance?->date ? $task->attendance->date->format('Y-m-d') : 'N/A';

        return $this->send(
            toEmail: $task->classroom->teacher->user->email,
            toName: $task->classroom->teacher->user->name,
            subject: 'TrackTern Notification: New Accomplishment Report Submitted',
            htmlBody: "
                <div style='font-family: Arial, sans-serif; padding: 20px; color: #1E1E1E;'>
                    <h2 style='color: #004798;'>New Accomplishment Report</h2>
                    <p><strong>" . e($studentName) . "</strong> submitted an accomplishment report for your review.</p>
                    <ul>
                        <li><strong>Title:</strong> " . e($task->title) . "</li>
                        <li><strong>Category:</strong> " . e($task->category) . "</li>
                        <li><strong>DTR Date:</strong> {$dtrDate}</li>
                        <li><strong>Classroom:</strong> " . e($task->classroom->classroom_name) . "</li>
                    </ul>
                    <p>Please review it in Classroom Management or the Task Approval Queue.</p>
                    <hr style='border: none; border-top: 1px solid #E5E7EB;'>
                    <p style='font-size: 12px; color: #6B7280;'>TrackTern Student Internship Monitoring System</p>
                </div>
            ",
            textBody: "{$studentName} submitted an accomplishment report titled {$task->title} for DTR date {$dtrDate}."
        );
    }

    public function sendAttendanceNotification(Attendance $attendance, string $actionType): bool
    {
        $attendance->loadMissing('student.user');

        if (!$attendance->student?->user?->email) {
            return false;
        }

        $subject = $actionType === 'time-in'
            ? 'TrackTern Notification: DTR Time In Recorded'
            : 'TrackTern Notification: DTR Time Out Recorded';
        $timestamp = $actionType === 'time-in' ? $attendance->time_in : $attendance->time_out;
        $renderedLine = $actionType === 'time-out'
            ? '<li><strong>Rendered Time:</strong> ' . number_format($attendance->rendered_hours, 2) . ' hours</li>'
            : '';

        return $this->send(
            toEmail: $attendance->student->user->email,
            toName: $attendance->student->user->name,
            subject: $subject,
            htmlBody: "
                <div style='font-family: Arial, sans-serif; padding: 20px; color: #1E1E1E;'>
                    <h2 style='color: #004798;'>TrackTern DTR Notification</h2>
                    <p>Hello <strong>" . e($attendance->student->user->name) . "</strong>,</p>
                    <p>Your DTR <strong>" . strtoupper($actionType) . "</strong> has been recorded successfully.</p>
                    <ul>
                        <li><strong>Student Code / ID:</strong> " . e($attendance->student->intern_id) . "</li>
                        <li><strong>Date:</strong> " . optional($attendance->date)->format('Y-m-d') . "</li>
                        <li><strong>Timestamp:</strong> " . optional($timestamp)->format('Y-m-d h:i:s A') . "</li>
                        {$renderedLine}
                    </ul>
                    <hr style='border: none; border-top: 1px solid #E5E7EB;'>
                    <p style='font-size: 12px; color: #6B7280;'>TrackTern Student Internship Monitoring System</p>
                </div>
            ",
            textBody: "Your TrackTern DTR {$actionType} was recorded at " . optional($timestamp)->format('Y-m-d h:i:s A') . '.'
        );
    }

    public function sendWeeklyReportNotification(WeeklyReport $report, string $actionType): bool
    {
        $report->loadMissing('student.user');

        if (!$report->student?->user?->email) {
            return false;
        }

        $statusText = $actionType === 'approve' ? 'Approved' : 'Revision Requested';
        $statusColor = $actionType === 'approve' ? '#007A33' : '#DC2626';
        $feedback = $report->teacher_feedback ?: 'None provided';

        return $this->send(
            toEmail: $report->student->user->email,
            toName: $report->student->user->name,
            subject: "TrackTern Notification: Weekly Report #{$report->week_number} {$statusText}",
            htmlBody: "
                <div style='font-family: Arial, sans-serif; padding: 20px; color: #1E1E1E;'>
                    <h2 style='color: #004798;'>TrackTern Weekly Report Update</h2>
                    <p>Hello <strong>" . e($report->student->user->name) . "</strong>,</p>
                    <p>Your <strong>Week #{$report->week_number} Progress Report</strong> has been reviewed by your instructor.</p>
                    <ul>
                        <li><strong>Status:</strong> <span style='color: {$statusColor}; font-weight: bold;'>{$statusText}</span></li>
                        <li><strong>Coverage Period:</strong> " . optional($report->coverage_start_date)->format('Y-m-d') . ' to ' . optional($report->coverage_end_date)->format('Y-m-d') . "</li>
                        <li><strong>Teacher Feedback:</strong> " . e($feedback) . "</li>
                    </ul>
                    <hr style='border: none; border-top: 1px solid #E5E7EB;'>
                    <p style='font-size: 12px; color: #6B7280;'>TrackTern Student Internship Monitoring System</p>
                </div>
            ",
            textBody: "Your TrackTern weekly report #{$report->week_number} is {$statusText}. Feedback: {$feedback}"
        );
    }

    public function send(string $toEmail, ?string $toName, string $subject, string $htmlBody, ?string $textBody = null): bool
    {
        $transport = (string) config('mail.default', 'log');

        if (in_array($transport, ['log', 'array'], true)) {
            Log::info('PHPMailer email captured by local mail mode.', [
                'to' => $toEmail,
                'subject' => $subject,
                'html' => $htmlBody,
            ]);

            return true;
        }

        $mailer = new PHPMailer(true);
        $mailer->CharSet = PHPMailer::CHARSET_UTF8;
        $mailer->isHTML(true);

        $this->configureTransport($mailer, $transport);

        $from = config('mail.from.address', 'hello@example.com');
        $fromName = config('mail.from.name', config('app.name', 'TrackTern'));
        $mailer->setFrom($from, $fromName);
        $mailer->addAddress($toEmail, $toName ?: '');
        $mailer->Subject = $subject;
        $mailer->Body = $htmlBody;
        $mailer->AltBody = $textBody ?: trim(strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody)));

        try {
            return $mailer->send();
        } catch (\Throwable $e) {
            Log::error('PHPMailer send failed.', [
                'to' => $toEmail,
                'subject' => $subject,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function configureTransport(PHPMailer $mailer, string $transport): void
    {
        if ($transport === 'smtp') {
            $smtp = config('mail.mailers.smtp', []);
            $mailer->isSMTP();
            $mailer->Host = $smtp['host'] ?? '127.0.0.1';
            $mailer->Port = (int) ($smtp['port'] ?? 2525);
            $mailer->Timeout = (int) ($smtp['timeout'] ?? 30);
            $mailer->SMTPAuth = !empty($smtp['username']);
            $mailer->Username = $smtp['username'] ?? '';
            $mailer->Password = $smtp['password'] ?? '';

            if (!empty($smtp['scheme'])) {
                $mailer->SMTPSecure = $smtp['scheme'];
            }

            return;
        }

        if ($transport === 'sendmail') {
            $mailer->isSendmail();
            $mailer->Sendmail = config('mail.mailers.sendmail.path', ini_get('sendmail_path') ?: '/usr/sbin/sendmail -bs -i');
            return;
        }

        $mailer->isMail();
    }
}
