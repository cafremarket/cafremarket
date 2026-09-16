<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('config_stripes');
    }

    public function down(): void
    {
        // Historical gateway table is not restored.
    }
};
