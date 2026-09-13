<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('popups')) {
            return;
        }

        Schema::create('popups', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('headline')->nullable();
            $table->text('description')->nullable();
            $table->string('button_label')->nullable();
            $table->string('button_link')->nullable();
            $table->string('bg_color')->nullable();

            $table->string('platform')->default('all'); // all | web | app
            $table->string('page')->default('all'); // all | home | product | category | cart | checkout
            $table->string('user_type')->default('all'); // all | guest | customer
            $table->string('frequency')->default('once_per_session'); // every_page_load | once_per_session | once_per_day | once_only

            $table->unsignedInteger('delay_ms')->default(2000);
            $table->integer('priority')->default(100);
            $table->boolean('active')->default(true);

            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();

            $table->timestamps();

            $table->index(['platform', 'page', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('popups');
    }
};
