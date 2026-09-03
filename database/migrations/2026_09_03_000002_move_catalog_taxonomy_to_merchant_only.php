<?php

use App\Models\Role;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Move catalog taxonomy (Category / Attribute / Manufacturer) to merchant-only access.
     * Category Groups / Sub Groups remain platform modules but are unused (UI + admin routes removed).
     */
    public function up(): void
    {
        $now = Carbon::now();
        $merchantModules = ['Category', 'Attribute', 'Manufacturer'];

        foreach ($merchantModules as $moduleName) {
            DB::table('modules')->where('name', $moduleName)->update(['access' => 'Merchant']);

            $module = DB::table('modules')->where('name', $moduleName)->first();
            if (! $module) {
                continue;
            }

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

                // Remove platform admin role access so catalog taxonomy is store-only.
                DB::table('permission_role')
                    ->where('permission_id', $permissionId)
                    ->where('role_id', Role::ADMIN)
                    ->delete();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('modules')->whereIn('name', ['Category', 'Attribute', 'Manufacturer'])
            ->update(['access' => 'Common']);
    }
};
