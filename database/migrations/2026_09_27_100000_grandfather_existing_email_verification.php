<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Email verification is enforced from now on; accounts that already exist
 * are treated as verified so real users aren't suddenly blocked.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('customers')->whereNotNull('verification_token')->update(['verification_token' => null]);

        DB::table('users')->whereNotNull('verification_token')->update([
            'verification_token' => null,
            'email_verified_at' => DB::raw('COALESCE(email_verified_at, NOW())'),
        ]);
    }

    public function down(): void
    {
        // Irreversible: the old tokens were never needed again.
    }
};
