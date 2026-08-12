<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePushCampaignsTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('push_campaigns')) {
            return;
        }

        Schema::create('push_campaigns', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->text('body');
            $table->string('image_url')->nullable();
            $table->string('audience', 32)->default('customers'); // customers|vendors|delivery|all
            $table->string('type', 32)->default('promotion'); // promotion|announcement|order|chat|custom
            $table->string('deep_link')->nullable();
            $table->json('data')->nullable();
            $table->string('status', 20)->default('draft'); // draft|queued|sending|sent|failed
            $table->unsignedInteger('target_count')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->text('error_message')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'audience']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('push_campaigns');
    }
}
