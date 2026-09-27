<?php

namespace App\Services\Cron;

use Carbon\Carbon;
use Illuminate\Console\Events\ScheduledBackgroundTaskFinished;
use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskSkipped;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Console\Scheduling\CallbackEvent;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Records every scheduled task run in cron_job_logs (times in UTC) and a once-a-minute
 * heartbeat that tells whether the server cron (schedule:run) is running at all.
 * Logging problems are swallowed: monitoring must never break a job.
 */
class CronMonitor
{
    public const HEARTBEAT_TASK = 'scheduler-heartbeat';

    public const HEARTBEAT_OPTION = 'cron_scheduler_heartbeat';

    /** Scheduler counts as running if it checked in within this many minutes. */
    public const ALIVE_MINUTES = 3;

    public const KEEP_DAYS = 30;

    private const MAX_OUTPUT = 20000;

    /** Log row id per running event (same process). */
    private static array $running = [];

    /** Set by cron:run-now so manual runs are labelled with the admin who started them. */
    public static ?string $triggeredBy = null;

    public static function register($events): void
    {
        $events->listen(ScheduledTaskStarting::class, fn ($e) => static::safely(fn () => static::starting($e->task)));
        $events->listen(ScheduledTaskFinished::class, fn ($e) => static::safely(fn () => static::finished($e->task, $e->runtime)));
        $events->listen(ScheduledTaskFailed::class, fn ($e) => static::safely(fn () => static::failed($e->task, $e->exception)));
        $events->listen(ScheduledTaskSkipped::class, fn ($e) => static::safely(fn () => static::skipped($e->task)));
        $events->listen(ScheduledBackgroundTaskFinished::class, fn ($e) => static::safely(fn () => static::backgroundFinished($e->task)));
    }

    /**
     * Add the heartbeat and log pruning, and capture each command's output in a file.
     * Call at the end of Console\Kernel::schedule().
     */
    public static function instrument(Schedule $schedule): void
    {
        $schedule->call(fn () => static::heartbeat())->everyMinute()->name(static::HEARTBEAT_TASK);
        $schedule->call(fn () => static::prune())->dailyAt('03:30')->name('Prune cron job logs');

        foreach ($schedule->events() as $event) {
            if (! $event instanceof CallbackEvent && $event->output === $event->getDefaultOutput()) {
                File::ensureDirectoryExists(storage_path('logs/cron'));
                $event->sendOutputTo(static::outputFile($event));
            }
        }
    }

    public static function heartbeat(): void
    {
        DB::table('options')->updateOrInsert(
            ['option_name' => static::HEARTBEAT_OPTION],
            ['option_value' => now('UTC')->toDateTimeString(), 'autoload' => 0, 'updated_at' => now('UTC')]
        );
    }

    public static function lastHeartbeat(): ?Carbon
    {
        $value = DB::table('options')->where('option_name', static::HEARTBEAT_OPTION)->value('option_value');

        return $value ? static::fromUtc($value) : null;
    }

    public static function isSchedulerRunning(): bool
    {
        $last = static::lastHeartbeat();

        return $last !== null && $last->gt(now('UTC')->subMinutes(static::ALIVE_MINUTES));
    }

    public static function prune(): int
    {
        return DB::table('cron_job_logs')->where('started_at', '<', now('UTC')->subDays(static::KEEP_DAYS))->delete();
    }

    /**
     * Scheduled tasks as defined in Console\Kernel::schedule().
     *
     * @return Collection<int, array{task: string, command: string, expression: string, next_run: ?Carbon, background: bool, overlapping: bool}>
     */
    public static function scheduledTasks(): Collection
    {
        app(ConsoleKernel::class); // defines the schedule when resolved outside the console

        return collect(app(Schedule::class)->events())
            ->map(fn (Event $event) => [
                'task' => static::taskName($event),
                'command' => static::commandLine($event),
                'expression' => $event->expression,
                'next_run' => rescue(fn () => Carbon::instance($event->nextRunDate()), null, false),
                'background' => (bool) $event->runInBackground,
                'overlapping' => (bool) $event->withoutOverlapping,
            ])
            ->reject(fn ($task) => $task['task'] === static::HEARTBEAT_TASK)
            ->values();
    }

    /** The scheduled event with this task name (heartbeat excluded), or null. */
    public static function findEvent(string $task): ?Event
    {
        app(ConsoleKernel::class); // defines the schedule when resolved outside the console

        return collect(app(Schedule::class)->events())
            ->first(fn (Event $event) => ! static::ignored($event) && static::taskName($event) === $task);
    }

    /**
     * Run one scheduled task now, exactly as schedule:run does (same events, same
     * withoutOverlapping lock, same output capture), so it shows up in the log.
     * Background tasks run in the foreground here: we are already a separate process.
     */
    public static function runNow(Event $event): int
    {
        $events = app('events');
        $event->runInBackground = false;

        $events->dispatch(new ScheduledTaskStarting($event));
        $start = microtime(true);

        try {
            $event->run(app());
            $events->dispatch(new ScheduledTaskFinished($event, round(microtime(true) - $start, 2)));
        } catch (\Throwable $e) {
            $events->dispatch(new ScheduledTaskFailed($event, $e));

            return 1;
        }

        return (int) ($event->exitCode ?? 0);
    }

    public static function taskName(Event $event): string
    {
        if ($event->description) {
            return $event->description;
        }

        $command = (string) $event->command;

        // "'/usr/bin/php' 'artisan' affiliate:release-commissions" -> "affiliate:release-commissions"
        if (preg_match("/artisan'?\\s+(.+)$/", $command, $m)) {
            return trim($m[1]);
        }

        return $command !== '' ? $command : 'Closure';
    }

    protected static function starting(Event $event): void
    {
        if (static::ignored($event) || ! static::ready()) {
            return;
        }

        // A run skipped by withoutOverlapping never sets an exit code; clear any stale one.
        $event->exitCode = null;

        static::$running[spl_object_id($event)] = DB::table('cron_job_logs')->insertGetId([
            'task' => static::taskName($event),
            'command' => static::commandLine($event),
            'expression' => $event->expression,
            'triggered_by' => static::$triggeredBy,
            'status' => 'running',
            'started_at' => now('UTC'),
            'created_at' => now('UTC'),
            'updated_at' => now('UTC'),
        ]);
    }

    protected static function finished(Event $event, $runtime): void
    {
        $id = static::$running[spl_object_id($event)] ?? null;

        if (! $id || static::ignored($event)) {
            return;
        }

        // Background tasks report their result later (schedule:finish).
        if ($event->runInBackground && ! $event instanceof CallbackEvent) {
            return;
        }

        unset(static::$running[spl_object_id($event)]);

        if ($event->exitCode === null && ! $event instanceof CallbackEvent) {
            // Run was skipped: the previous run still holds the withoutOverlapping lock.
            static::update($id, ['status' => 'skipped', 'error' => trans('cron.skipped_overlap')], $runtime);

            return;
        }

        $current = DB::table('cron_job_logs')->where('id', $id)->value('status');
        if ($current === 'failed') {
            return; // ScheduledTaskFailed already recorded the error
        }

        static::update($id, [
            'status' => (int) $event->exitCode === 0 ? 'success' : 'failed',
            'exit_code' => $event->exitCode,
            'output' => static::readOutput($event),
        ], $runtime);
    }

    protected static function failed(Event $event, \Throwable $e): void
    {
        $id = static::$running[spl_object_id($event)] ?? null;

        if (! $id || static::ignored($event)) {
            return;
        }

        unset(static::$running[spl_object_id($event)]);

        static::update($id, [
            'status' => 'failed',
            'exit_code' => $event->exitCode,
            'error' => mb_substr(get_class($e).': '.$e->getMessage(), 0, 2000),
            'output' => static::readOutput($event),
        ]);
    }

    protected static function skipped(Event $event): void
    {
        if (static::ignored($event) || ! static::ready()) {
            return;
        }

        DB::table('cron_job_logs')->insert([
            'task' => static::taskName($event),
            'command' => static::commandLine($event),
            'expression' => $event->expression,
            'triggered_by' => static::$triggeredBy,
            'status' => 'skipped',
            'error' => trans('cron.skipped_filter'),
            'started_at' => now('UTC'),
            'finished_at' => now('UTC'),
            'duration_ms' => 0,
            'created_at' => now('UTC'),
            'updated_at' => now('UTC'),
        ]);
    }

    /** Runs in the separate "schedule:finish" process started by a background task. */
    protected static function backgroundFinished(Event $event): void
    {
        if (static::ignored($event) || ! static::ready()) {
            return;
        }

        $row = DB::table('cron_job_logs')
            ->where('task', static::taskName($event))
            ->where('status', 'running')
            ->latest('id')
            ->first();

        if (! $row) {
            return;
        }

        $runtime = $row->started_at ? Carbon::parse($row->started_at, 'UTC')->diffInMilliseconds(now('UTC')) / 1000 : null;

        static::update($row->id, [
            'status' => (int) $event->exitCode === 0 ? 'success' : 'failed',
            'exit_code' => $event->exitCode,
            'output' => static::readOutput($event),
        ], $runtime);
    }

    /**
     * Times are stored in UTC: the scheduler runs in UTC while web pages switch to
     * the store's timezone, so convert for display.
     */
    public static function fromUtc($value): ?Carbon
    {
        return $value ? Carbon::parse($value, 'UTC')->setTimezone(date_default_timezone_get()) : null;
    }

    protected static function update(int $id, array $values, $runtimeSeconds = null): void
    {
        DB::table('cron_job_logs')->where('id', $id)->update($values + [
            'finished_at' => now('UTC'),
            'duration_ms' => $runtimeSeconds !== null ? (int) round(abs((float) $runtimeSeconds) * 1000) : null,
            'updated_at' => now('UTC'),
        ]);
    }

    protected static function readOutput(Event $event): ?string
    {
        $file = $event->output;

        if (! $file || $file === $event->getDefaultOutput() || ! is_file($file)) {
            return null;
        }

        $output = trim((string) @file_get_contents($file));

        if ($output === '') {
            return null;
        }

        return mb_strlen($output) > static::MAX_OUTPUT
            ? '…'.mb_substr($output, -static::MAX_OUTPUT)
            : $output;
    }

    protected static function outputFile(Event $event): string
    {
        return storage_path('logs/cron/'.sha1($event->mutexName()).'.log');
    }

    protected static function commandLine(Event $event): string
    {
        if ($event instanceof CallbackEvent) {
            return $event->description ?: 'Closure';
        }

        return 'php artisan '.static::taskName($event);
    }

    protected static function ignored(Event $event): bool
    {
        return $event->description === static::HEARTBEAT_TASK;
    }

    protected static function ready(): bool
    {
        static $ready = null;

        return $ready ??= Schema::hasTable('cron_job_logs');
    }

    protected static function safely(callable $fn): void
    {
        try {
            $fn();
        } catch (\Throwable $e) {
            Log::warning('Cron monitor: '.$e->getMessage());
        }
    }
}
