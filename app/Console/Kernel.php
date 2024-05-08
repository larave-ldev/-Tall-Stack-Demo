<?php

namespace App\Console;

use App\Mail\sendEmailOnCommandFailed;
use App\Models\cronSchedule;
use Carbon\Carbon;
use Cron\CronExpression;
use Exception;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Mail;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $developerEmailId = config('app.developer_mail_id');

        CronSchedule::where('status', '=', '1')
            ->each(function ($cronSchedule) use ($schedule, $developerEmailId) {
                if ($this->matchesSchedule(now(), $cronSchedule->schedule)) {
                    $this->startProcess($cronSchedule);

                    try {
                        $schedule->command($cronSchedule->command_name)
                            ->cron($cronSchedule->schedule)
                            ->after(function () use ($cronSchedule) {
                                $this->endProcess($cronSchedule);
                            })->onFailure(function () use ($developerEmailId, $cronSchedule) {
                                $this->sendCommandFailedEmails($developerEmailId, $cronSchedule, 'Command failed');
                            })->withoutOverlapping()
                            ->onOneServer();
                    } catch (Exception $exception) {
                        // Send email on command failure
                        $this->sendCommandFailedEmails($developerEmailId, $cronSchedule, 'Command overlapping', $exception->getMessage());

                    }
                }
            });

    }

    protected function matchesSchedule($currentTime, $cronSchedule): bool
    {
        // Parse the Cron schedule and check if it matches the current time
        $cronParser = new CronExpression($cronSchedule);

        return $cronParser->isDue($currentTime);
    }

    protected function startProcess(CronSchedule $cronSchedule): void
    {
        $cronSchedule->update([
            'status' => '2',
            'start_time' => now(),
            'end_time' => null,
            'duration_seconds' => null,
        ]);
    }

    protected function endProcess(CronSchedule $cronSchedule): void
    {
        $startTime = Carbon::parse($cronSchedule->start_time);
        $endTime = now();
        $durationSeconds = $endTime->diffInSeconds($startTime);

        $cronSchedule->update([
            'status' => '1',
            'end_time' => $endTime,
            'duration_seconds' => $durationSeconds,
        ]);
    }

    protected function sendCommandFailedEmails($developerEmailId, $cronSchedule, $subject, $errorMessage = null): void
    {
        if (! empty($developerEmailId)) {
            Mail::to($developerEmailId)->send(new SendEmailOnCommandFailed($cronSchedule, $subject, $errorMessage));
        }
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
