<?php

namespace Incevio\Package\Affiliate;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class Uninstaller
{
    public $package;

    public function __construct()
    {
        $this->package = 'affiliate';
    }

    public function cleanDatabase()
    {
        if (Schema::hasTable('affiliates') && Schema::hasTable('affiliate_links')) {
            DB::table('affiliate_links')->delete();
            DB::table('affiliates')->delete();
        
            Log::info("Cleaning successfully done for " . $this->package);

            return true;
        }

        Log::info("Cleaning FAILED: " . $this->package);

        throw new \Exception('Package data cleaning action failed: ' . $this->package);
    }
}
