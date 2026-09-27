<?php

namespace App\Services\Reports;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;

/**
 * Date range for a report, plus the equally long period right before it
 * (used for "vs previous period" comparisons).
 */
final class ReportPeriod
{
    public const DEFAULT_DAYS = 30;

    /** Longest range a report may cover, to keep queries bounded. */
    public const MAX_DAYS = 1100;

    public function __construct(
        public readonly Carbon $from,
        public readonly Carbon $to,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $to = self::parse($request->get('to')) ?? Carbon::today();
        $from = self::parse($request->get('from')) ?? $to->copy()->subDays(self::DEFAULT_DAYS - 1);

        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        if ($from->diffInDays($to) > self::MAX_DAYS) {
            $from = $to->copy()->subDays(self::MAX_DAYS);
        }

        return new self($from->copy()->startOfDay(), $to->copy()->endOfDay());
    }

    public function previous(): self
    {
        $days = $this->days();

        return new self(
            $this->from->copy()->subDays($days)->startOfDay(),
            $this->from->copy()->subDay()->endOfDay(),
        );
    }

    public function days(): int
    {
        return (int) $this->from->copy()->startOfDay()->diffInDays($this->to->copy()->startOfDay()) + 1;
    }

    /** Charts switch to monthly buckets for long ranges. */
    public function groupsByMonth(): bool
    {
        return $this->days() > 62;
    }

    public function bucketKey($date): string
    {
        $date = $date instanceof Carbon ? $date : Carbon::parse($date);

        return $this->groupsByMonth() ? $date->format('Y-m') : $date->format('Y-m-d');
    }

    /**
     * Every bucket in the range, so charts show gaps as zero instead of skipping them.
     *
     * @return array<string, string> bucket key => display label
     */
    public function buckets(): array
    {
        $buckets = [];

        if ($this->groupsByMonth()) {
            $cursor = $this->from->copy()->startOfMonth();
            while ($cursor->lte($this->to)) {
                $buckets[$cursor->format('Y-m')] = $cursor->translatedFormat('M Y');
                $cursor->addMonth();
            }

            return $buckets;
        }

        foreach (CarbonPeriod::create($this->from->copy()->startOfDay(), $this->to->copy()->startOfDay()) as $day) {
            $buckets[$day->format('Y-m-d')] = $day->translatedFormat('d M');
        }

        return $buckets;
    }

    /** Query-string parameters that reproduce this period. */
    public function query(): array
    {
        return ['from' => $this->from->toDateString(), 'to' => $this->to->toDateString()];
    }

    private static function parse($value): ?Carbon
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
        } catch (\Throwable $e) {
            return null;
        }
    }
}
