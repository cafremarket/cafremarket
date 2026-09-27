<?php

namespace App\Http\Controllers\Admin\Report;

use App\Http\Controllers\Controller;
use App\Services\Cron\CronMonitor;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Admin page: is the scheduler (server cron) running, what is scheduled,
 * and the log of every scheduled task run.
 */
class CronJobController extends Controller
{
    /** A "running" row older than this is shown as interrupted (process died). */
    private const STUCK_HOURS = 6;

    public const STATUSES = ['running', 'success', 'failed', 'skipped'];

    public function index(Request $request)
    {
        $ready = Schema::hasTable('cron_job_logs');
        $since = now('UTC')->subDay(); // log times are stored in UTC

        $stats = $ready
            ? DB::table('cron_job_logs')
                ->where('started_at', '>=', $since)
                ->groupBy('task')
                ->selectRaw("task, COUNT(*) as runs, SUM(status = 'failed') as failures, AVG(CASE WHEN status = 'success' THEN duration_ms END) as avg_ms")
                ->get()->keyBy('task')
            : collect();

        $lastRuns = $ready
            ? DB::table('cron_job_logs')
                ->whereIn('id', DB::table('cron_job_logs')->groupBy('task')->selectRaw('MAX(id)'))
                ->get()->keyBy('task')
            : collect();

        $tasks = CronMonitor::scheduledTasks()->map(function ($task) use ($stats, $lastRuns) {
            $last = $lastRuns->get($task['task']);

            return $task + [
                'frequency' => $this->describe($task['expression']),
                'last' => $last ? $this->decorate($last) : null,
                'runs_24h' => (int) ($stats->get($task['task'])->runs ?? 0),
                'failures_24h' => (int) ($stats->get($task['task'])->failures ?? 0),
                'avg_ms' => isset($stats->get($task['task'])->avg_ms) ? (int) $stats->get($task['task'])->avg_ms : null,
            ];
        });

        $filters = [
            'task' => (string) $request->get('task', ''),
            'status' => in_array($request->get('status'), self::STATUSES, true) ? $request->get('status') : '',
        ];

        $logs = $ready
            ? DB::table('cron_job_logs')
                ->when($filters['task'] !== '', fn ($q) => $q->where('task', $filters['task']))
                ->when($filters['status'] !== '', fn ($q) => $q->where('status', $filters['status']))
                ->orderByDesc('id')
                ->paginate(50)
                ->withQueryString()
                ->through(fn ($row) => $this->decorate($row))
            : null;

        $summary = [
            'runs' => (int) $stats->sum('runs'),
            'failures' => (int) $stats->sum('failures'),
            'running' => $ready ? DB::table('cron_job_logs')->where('status', 'running')->where('started_at', '>=', now('UTC')->subHours(self::STUCK_HOURS))->count() : 0,
        ];

        return view('admin.report.platform.cron', [
            'ready' => $ready,
            'running' => CronMonitor::isSchedulerRunning(),
            'lastHeartbeat' => CronMonitor::lastHeartbeat(),
            'cronLine' => '* * * * * cd '.base_path().' && php artisan schedule:run >> /dev/null 2>&1',
            'tasks' => $tasks,
            'summary' => $summary,
            'logs' => $logs,
            'filters' => $filters,
            'taskNames' => $tasks->pluck('task')->merge($lastRuns->keys())->unique()->sort()->values(),
        ]);
    }

    /**
     * "Run now": start one scheduled task immediately. Only names from the schedule
     * are accepted (never an arbitrary command). It runs as a separate background
     * process so long jobs don't hit the web request timeout; if the host blocks
     * exec(), it runs inside this request instead.
     */
    public function run(Request $request)
    {
        $task = (string) $request->input('task');
        $event = CronMonitor::findEvent($task);

        abort_unless($event, 404);

        $user = $request->user();
        $by = trans('cron.manual_by', ['name' => $user->name ?: $user->email]);

        if ($this->launchInBackground($task, $by)) {
            return redirect()->route('admin.report.cron', ['task' => $task])
                ->with('success', trans('cron.run_started', ['task' => $task]));
        }

        @set_time_limit(0);
        CronMonitor::$triggeredBy = $by;
        $exitCode = CronMonitor::runNow($event);

        return redirect()->route('admin.report.cron', ['task' => $task])
            ->with($exitCode === 0 ? 'success' : 'error', trans($exitCode === 0 ? 'cron.run_finished' : 'cron.run_failed', ['task' => $task]));
    }

    private function launchInBackground(string $task, string $by): bool
    {
        $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));

        if (PHP_OS_FAMILY === 'Windows' || ! function_exists('exec') || in_array('exec', $disabled, true)) {
            return false;
        }

        $command = sprintf(
            'cd %s && nohup %s artisan cron:run-now %s --by=%s > /dev/null 2>&1 &',
            escapeshellarg(base_path()),
            escapeshellarg($this->phpCliBinary()),
            escapeshellarg($task),
            escapeshellarg($by)
        );

        try {
            exec($command, $output, $code);
        } catch (\Throwable $e) {
            return false;
        }

        return $code === 0;
    }

    /** The PHP CLI binary (under PHP-FPM, PHP_BINARY points at php-fpm). */
    private function phpCliBinary(): string
    {
        if ($configured = config('system.php_cli_binary')) {
            return $configured;
        }

        if (PHP_BINARY && ! str_contains(basename(PHP_BINARY), 'fpm') && is_executable(PHP_BINARY)) {
            return PHP_BINARY;
        }

        $sibling = PHP_BINDIR.'/php';

        return is_executable($sibling) ? $sibling : 'php';
    }

    /** Adds display fields; a long-"running" row whose process died is shown as interrupted. */
    private function decorate($row)
    {
        $started = CronMonitor::fromUtc($row->started_at);
        $status = $row->status;

        if ($status === 'running' && $started && $started->lt(now()->subHours(self::STUCK_HOURS))) {
            $status = 'interrupted';
        }

        $row->display_status = $status;
        $row->started = $started;
        $row->duration = $row->duration_ms !== null ? $this->formatDuration((int) $row->duration_ms) : null;

        return $row;
    }

    private function formatDuration(int $ms): string
    {
        if ($ms < 1000) {
            return $ms.' ms';
        }

        $seconds = $ms / 1000;

        return $seconds < 60
            ? number_format($seconds, 1).' s'
            : floor($seconds / 60).' min '.((int) $seconds % 60).' s';
    }

    /** Plain-language version of the common cron expressions used in Console\Kernel. */
    private function describe(?string $expression): string
    {
        $parts = preg_split('/\s+/', trim((string) $expression));

        if (count($parts) !== 5) {
            return (string) $expression;
        }

        [$min, $hour, $day, $month, $week] = $parts;
        $everyDay = $day === '*' && $month === '*' && $week === '*';

        if (! $everyDay) {
            return $expression;
        }

        if ($min === '*' && $hour === '*') {
            return trans('cron.every_minute');
        }

        if (preg_match('/^\*\/(\d+)$/', $min, $m) && $hour === '*') {
            return trans('cron.every_n_minutes', ['count' => $m[1]]);
        }

        if (ctype_digit($min) && $hour === '*') {
            return (int) $min === 0 ? trans('cron.hourly') : trans('cron.hourly_at', ['minute' => sprintf('%02d', $min)]);
        }

        if (ctype_digit($min) && preg_match('/^\d+(,\d+)*$/', $hour)) {
            $times = collect(explode(',', $hour))->map(fn ($h) => sprintf('%02d:%02d', $h, $min))->implode(', ');

            return trans('cron.daily_at', ['time' => $times]);
        }

        return $expression;
    }
}
