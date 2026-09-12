<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('systems', function (Blueprint $table) {
            if (Schema::hasColumn('systems', 'allow_guest_checkout')) {
                $table->dropColumn('allow_guest_checkout');
            }
        });
    }

    public function down(): void
    {
        Schema::table('systems', function (Blueprint $table) {
            if (! Schema::hasColumn('systems', 'allow_guest_checkout')) {
                $table->boolean('allow_guest_checkout')->nullable()->default(true);
            }
        });
    }
};
