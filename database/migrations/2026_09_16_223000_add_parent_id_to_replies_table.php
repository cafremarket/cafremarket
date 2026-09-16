<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddParentIdToRepliesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasTable('replies') || Schema::hasColumn('replies', 'parent_id')) {
            return;
        }

        Schema::table('replies', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_id')->nullable()->after('repliable_type');
            $table->index('parent_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (! Schema::hasTable('replies') || ! Schema::hasColumn('replies', 'parent_id')) {
            return;
        }

        Schema::table('replies', function (Blueprint $table) {
            $table->dropIndex(['parent_id']);
            $table->dropColumn('parent_id');
        });
    }
}
