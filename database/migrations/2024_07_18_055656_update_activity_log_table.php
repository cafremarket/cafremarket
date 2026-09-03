<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateActivityLogTable extends Migration
{
    public $connection;

    public $table;

    public function __construct()
    {
        $this->connection = config('activitylog.database_connection');
        $this->table = config('activitylog.table_name');
    }

    public function up()
    {
        $schema = Schema::connection($this->connection);

        $schema->table($this->table, function (Blueprint $table) use ($schema) {
            if (! $schema->hasColumn($this->table, 'event')) {
                $table->string('event')->nullable()->after('subject_type');
            }
            if (! $schema->hasColumn($this->table, 'batch_uuid')) {
                $table->uuid('batch_uuid')->nullable()->after('properties');
            }
        });
    }

    public function down()
    {
        $schema = Schema::connection($this->connection);

        $schema->table($this->table, function (Blueprint $table) use ($schema) {
            if ($schema->hasColumn($this->table, 'batch_uuid')) {
                $table->dropColumn('batch_uuid');
            }

            if ($schema->hasColumn($this->table, 'event')) {
                $table->dropColumn('event');
            }
        });
    }
}
