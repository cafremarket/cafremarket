<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

class ProductVideoFile implements ValidationRule
{
    public const MAX_SECONDS = 30;

    public const MAX_KILOBYTES = 10240; // 10 MB

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile || ! $value->isValid()) {
            $fail(trans('validation.product_video_invalid'));

            return;
        }

        $allowed = ['mp4', 'webm', 'mov', 'm4v'];
        $ext = strtolower($value->getClientOriginalExtension() ?: $value->extension());

        if (! in_array($ext, $allowed, true)) {
            $fail(trans('validation.product_video_mimes'));

            return;
        }

        if ($value->getSize() > self::MAX_KILOBYTES * 1024) {
            $fail(trans('validation.product_video_max'));

            return;
        }

        $duration = $this->probeDuration($value->getRealPath());

        if ($duration !== null && $duration > self::MAX_SECONDS + 0.5) {
            $fail(trans('validation.product_video_duration', ['seconds' => self::MAX_SECONDS]));
        }
    }

    /**
     * Best-effort duration probe (ffprobe). Returns null when unavailable.
     */
    private function probeDuration(string $path): ?float
    {
        if (! is_file($path)) {
            return null;
        }

        $ffprobe = trim((string) @shell_exec(
            'ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 '
            .escapeshellarg($path).' 2>&1'
        ));

        if ($ffprobe !== '' && is_numeric($ffprobe)) {
            return (float) $ffprobe;
        }

        return null;
    }
}
