<?php

use App\Models\Role;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('categories', 'shop_id')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->unsignedInteger('shop_id')->nullable()->after('id')->index();
            });
        }

        // Allow merchants to manage categories (store-scoped).
        DB::table('modules')->where('name', 'Category')->update(['access' => 'Common']);

        $now = Carbon::now();
        $module = DB::table('modules')->where('name', 'Category')->first();

        if ($module) {
            $actions = array_filter(array_map('trim', explode(',', (string) $module->actions)));

            foreach ($actions as $action) {
                $slug = strtolower($action).'_'.Str::snake($module->name);

                $permissionId = DB::table('permissions')->where('slug', $slug)->value('id');

                if (! $permissionId) {
                    $permissionId = DB::table('permissions')->insertGetId([
                        'module_id' => $module->id,
                        'name' => Str::title($action),
                        'slug' => $slug,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }

                $exists = DB::table('permission_role')->where([
                    ['permission_id', '=', $permissionId],
                    ['role_id', '=', Role::MERCHANT],
                ])->exists();

                if (! $exists) {
                    DB::table('permission_role')->insert([
                        'permission_id' => $permissionId,
                        'role_id' => Role::MERCHANT,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('modules')->where('name', 'Category')->update(['access' => 'Platform']);

        if (Schema::hasColumn('categories', 'shop_id')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->dropColumn('shop_id');
            });
        }
    }
};
