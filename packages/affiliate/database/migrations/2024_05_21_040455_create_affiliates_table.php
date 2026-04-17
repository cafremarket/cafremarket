<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CreateAffiliatesTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('affiliates')) {
            Schema::create('affiliates', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('username')->unique();
                $table->string('email')->unique();
                $table->string('phone')->nullable();
                $table->string('password', 60);
                $table->string('api_token', 80)->nullable()->default(null);
                $table->boolean('active')->default(true);
                $table->timestampTz('last_visited_at')->nullable();
                $table->ipAddress('last_visited_from')->nullable();
                $table->timestamp('read_announcements_at')->nullable();
                $table->string('verification_token', 100)->nullable();
                $table->rememberToken();
                $table->softDeletes();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('affiliates');
    }
}
