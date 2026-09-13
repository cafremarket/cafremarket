<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('review_summaries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('reviewable_id');
            $table->string('reviewable_type');
            $table->decimal('rating', 4, 2)->default(0);
            $table->unsignedInteger('count')->default(0);
            $table->timestamps();

            $table->unique(['reviewable_id', 'reviewable_type'], 'review_summaries_reviewable_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_summaries');
    }
};
