<?php

namespace App\Common;

trait ReleasesUniqueIdentifiers
{
    /**
     * Free email/slug/name so the same values can be reused after delete.
     */
    public function releaseUniqueIdentifiers(): void
    {
        $stamp = $this->getKey().'_'.time();

        if (! empty($this->email) && ! str_starts_with($this->email, 'deleted_')) {
            $this->email = 'deleted_'.$stamp.'_'.$this->email;
        }

        if (! empty($this->slug) && ! str_starts_with($this->slug, 'deleted-')) {
            $this->slug = 'deleted-'.$stamp.'-'.$this->slug;
        }

        if (! empty($this->name) && ! str_starts_with((string) $this->name, '[deleted] ')) {
            $this->name = '[deleted] '.$stamp.' '.$this->name;
        }

        if (! empty($this->phone) && ! str_starts_with((string) $this->phone, 'deleted_')) {
            $this->phone = 'deleted_'.$stamp.'_'.$this->phone;
        }
    }

    public static function bootReleasesUniqueIdentifiers(): void
    {
        static::deleting(function ($model) {
            if ($model->isForceDeleting()) {
                return;
            }

            $model->releaseUniqueIdentifiers();
            $model->saveQuietly();
        });
    }
}
