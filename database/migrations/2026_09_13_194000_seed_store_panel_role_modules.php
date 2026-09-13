<?php

use App\Models\Role;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $now = Carbon::now();

        $this->seedModule('Delivery Boy', 'Merchant', 'view,add,edit,delete', $now);
        $this->renamePermissionSlug('requestdelete_review', 'request_delete_review', 'Request delete');

        DB::table('modules')->where('name', 'Review')->update([
            'actions' => 'view,reply,request_delete',
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        $this->renamePermissionSlug('request_delete_review', 'requestdelete_review', 'Requestdelete');

        $moduleId = DB::table('modules')->where('name', 'Delivery Boy')->value('id');
        if ($moduleId) {
            $permissionIds = DB::table('permissions')->where('module_id', $moduleId)->pluck('id');
            DB::table('permission_role')->whereIn('permission_id', $permissionIds)->delete();
            DB::table('permissions')->whereIn('id', $permissionIds)->delete();
            DB::table('modules')->where('id', $moduleId)->delete();
        }
    }

    private function seedModule(string $name, string $access, string $actionsCsv, Carbon $now): void
    {
        $moduleId = DB::table('modules')->where('name', $name)->value('id');

        if (! $moduleId) {
            $moduleId = DB::table('modules')->insertGetId([
                'name' => $name,
                'description' => 'Manage '.strtolower($name).'.',
                'access' => $access,
                'actions' => $actionsCsv,
                'active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            DB::table('modules')->where('id', $moduleId)->update([
                'access' => $access,
                'actions' => $actionsCsv,
                'updated_at' => $now,
            ]);
        }

        foreach (explode(',', $actionsCsv) as $action) {
            $slug = strtolower($action).'_'.Str::snake($name);
            $permissionId = DB::table('permissions')->where('slug', $slug)->value('id');

            if (! $permissionId) {
                $permissionId = DB::table('permissions')->insertGetId([
                    'module_id' => $moduleId,
                    'name' => Str::title(str_replace('_', ' ', $action)),
                    'slug' => $slug,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            if ($access !== 'Merchant' && ! DB::table('permission_role')->where([
                ['permission_id', '=', $permissionId],
                ['role_id', '=', Role::ADMIN],
            ])->exists()) {
                DB::table('permission_role')->insert([
                    'permission_id' => $permissionId,
                    'role_id' => Role::ADMIN,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            if ($access !== 'Platform' && ! DB::table('permission_role')->where([
                ['permission_id', '=', $permissionId],
                ['role_id', '=', Role::MERCHANT],
            ])->exists()) {
                DB::table('permission_role')->insert([
                    'permission_id' => $permissionId,
                    'role_id' => Role::MERCHANT,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    private function renamePermissionSlug(string $from, string $to, string $name): void
    {
        $row = DB::table('permissions')->where('slug', $from)->first();
        if ($row && ! DB::table('permissions')->where('slug', $to)->exists()) {
            DB::table('permissions')->where('id', $row->id)->update([
                'slug' => $to,
                'name' => $name,
            ]);
        }
    }
};
