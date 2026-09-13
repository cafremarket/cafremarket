<?php

use App\Models\Role;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Modules to seed: [name, access, actions].
     * - 'Review' (Common): both Admin and Merchant get view/reply/request-delete on their
     *   own shop's reviews (scoped in the controllers themselves, same as other Common
     *   modules).
     * - 'Review Moderation' (Platform): admin-only - approve/reject a seller's delete
     *   request, or delete a review directly.
     */
    private array $modules = [
        ['Review', 'Common', 'view,reply,request_delete'],
        ['Review Moderation', 'Platform', 'View,Approve,Reject,Delete'],
    ];

    public function up(): void
    {
        $now = Carbon::now();

        foreach ($this->modules as [$name, $access, $actionsCsv]) {
            $moduleId = DB::table('modules')->where('name', $name)->value('id');

            if (! $moduleId) {
                $moduleId = DB::table('modules')->insertGetId([
                    'name' => $name,
                    'access' => $access,
                    'actions' => $actionsCsv,
                    'active' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            foreach (explode(',', $actionsCsv) as $action) {
                $slug = strtolower($action).'_'.Str::snake($name);

                $permissionId = DB::table('permissions')->where('slug', $slug)->value('id');

                if (! $permissionId) {
                    $permissionId = DB::table('permissions')->insertGetId([
                        'module_id' => $moduleId,
                        'name' => Str::title($action),
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
    }

    public function down(): void
    {
        foreach ($this->modules as [$name, $access, $actionsCsv]) {
            $moduleId = DB::table('modules')->where('name', $name)->value('id');

            if (! $moduleId) {
                continue;
            }

            $permissionIds = DB::table('permissions')->where('module_id', $moduleId)->pluck('id');

            DB::table('permission_role')->whereIn('permission_id', $permissionIds)->delete();
            DB::table('permissions')->whereIn('id', $permissionIds)->delete();
            DB::table('modules')->where('id', $moduleId)->delete();
        }
    }
};
