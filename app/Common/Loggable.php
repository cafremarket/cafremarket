<?php

namespace App\Common;

use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

trait Loggable
{
    use LogsActivity;

    /**
     * The attributes that will be logged on activity logger.
     *
     * @var bool
     */
    protected static $logFillable = true;

    /**
     * The only attributes that has been changed.
     *
     * @var bool
     */
    protected static $logOnlyDirty = true;

    /**
     * Loggs for the loggable model
     *
     * @return [type] [description]
     */
    public function logs()
    {
        return $this->activities()->orderBy('created_at', 'desc')->get();
    }

    public function getActivitylogOptions(): LogOptions
    {
        $options = LogOptions::defaults();

        if (! empty(static::$logFillable)) {
            $options->logFillable();
        } else {
            $options->logAll();
        }

        if (! empty(static::$logOnlyDirty)) {
            $options->logOnlyDirty();
        }

        $options->dontSubmitEmptyLogs();

        if (
            property_exists(static::class, 'ignoreChangedAttributes')
            && is_array(static::$ignoreChangedAttributes)
            && static::$ignoreChangedAttributes !== []
        ) {
            $options->logExcept(static::$ignoreChangedAttributes);
        }

        if (property_exists(static::class, 'logName') && ! empty(static::$logName)) {
            $options->useLogName(static::$logName);
        }

        return $options;
    }
}
