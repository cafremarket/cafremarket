<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Vendors always see customer info; lock the flag on.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('systems', 'vendor_can_view_customer_info')) {
            return;
        }

        DB::table('systems')->update(['vendor_can_view_customer_info' => true]);
    }

    public function down(): void
    {
        // Intentionally left blank — feature stays always-on.
    }
};
