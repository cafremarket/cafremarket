<?php

namespace App\Models;

class Package extends BaseModel
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'packages';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'slug',
        'name',
        'description',
        'icon',
        'version',
        'compatible',
        'dependency',
        'active',
    ];

    /**
     * The attributes that should be casted to boolean types.
     *
     * @var array
     */
    protected $casts = [
        'active' => 'boolean',
    ];

    /**
     * Check it the package is compatible with the current cafremarket version.
     *
     * @var bool
     */
    public function isCompatible()
    {
        return version_compare($this->compatible, System::VERSION, '<=');
    }

    /**
     * Packages stay always active — deactivation is a no-op.
     *
     * @var void
     */
    public function deactivate()
    {
        if (! $this->active) {
            $this->active = true;
            $this->save();
        }
    }
}
