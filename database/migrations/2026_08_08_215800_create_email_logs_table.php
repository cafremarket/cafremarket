<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('email_logs')) {
            Schema::create('email_logs', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('to')->nullable()->index();
                $table->string('cc')->nullable();
                $table->string('bcc')->nullable();
                $table->string('subject')->nullable();
                $table->string('notification')->nullable()->index();
                $table->string('status', 20)->default('pending')->index(); // pending|sent|failed
                $table->text('error')->nullable();
                $table->string('context')->nullable();
                $table->nullableMorphs('related');
                $table->json('meta')->nullable();
                $table->timestamps();

                $table->index('created_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_logs');
    }
};
