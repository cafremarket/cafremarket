<?php

namespace App\Console\Commands;

use App\Services\Cron\CronMonitor;
use Illuminate\Console\Command;

/**
 * Run one scheduled task immediately (the admin Cron jobs page "Run now" button).
 */
class CronRunNow extends Command
{
    protected $signature = 'cron:run-now {task : Task name as shown on the Cron jobs page} {--by= : Who started it}';

    protected $description = 'Run a scheduled task now and record it in the cron log';

    public function handle(): int
    {
        $event = CronMonitor::findEvent((string) $this->argument('task'));

        if (! $event) {
            $this->error('Unknown scheduled task: '.$this->argument('task'));

            return self::FAILURE;
        }

        CronMonitor::$triggeredBy = $this->option('by') ?: trans('cron.manual');

        $exitCode = CronMonitor::runNow($event);

        $this->info('Finished with exit code '.$exitCode);

        return $exitCode === 0 ? self::SUCCESS : self::FAILURE;
    }
}
