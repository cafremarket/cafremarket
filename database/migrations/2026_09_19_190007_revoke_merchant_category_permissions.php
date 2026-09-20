<?php

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Inverts 2026_09_03_000001_add_shop_id_to_categories_and_enable_merchant_catalog.php:
 * categories go back to being fully platform/admin-managed. Stores may only
 * select from the published list, never create/edit/delete one.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('modules')->where('name', 'Category')->update(['access' => 'Platform']);

        $module = DB::table('modules')->where('name', 'Category')->first();

        if ($module) {
            $actions = array_filter(array_map('trim', explode(',', (string) $module->actions)));

            $permissionIds = [];

            foreach ($actions as $action) {
                $slug = strtolower($action).'_'.Str::snake($module->name);
                $permissionId = DB::table('permissions')->where('slug', $slug)->value('id');

                if ($permissionId) {
                    $permissionIds[] = $permissionId;
                }
            }

            if ($permissionIds) {
                DB::table('permission_role')
                    ->whereIn('permission_id', $permissionIds)
                    ->where('role_id', Role::MERCHANT)
                    ->delete();
            }
        }
    }

    /**
     * Best-effort: restores merchant access to the Category module the way
     * the 2026-09-03 migration originally granted it.
     */
    public function down(): void
    {
        DB::table('modules')->where('name', 'Category')->update(['access' => 'Common']);

        $now = now();
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
};
