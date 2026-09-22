<?php

use App\Models\Role;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Production is missing the Live Chat permission rows that local already has
 * (view_chat_conversation / reply_chat_conversation). Without them,
 * Gate::authorize('index', ChatConversation::class) redirects merchants back
 * to the dashboard with "This action is unauthorized."
 *
 * Idempotent — safe to run where ChatSeeder already ran.
 */
return new class extends Migration
{
    private string $moduleName = 'Chat Conversation';

    private string $access = 'Merchant';

    private string $actionsCsv = 'view,reply';

    public function up(): void
    {
        $now = Carbon::now();

        $moduleId = DB::table('modules')->where('name', $this->moduleName)->value('id');

        if (! $moduleId) {
            $moduleId = DB::table('modules')->insertGetId([
                'name' => $this->moduleName,
                'description' => 'Manage '.$this->moduleName,
                'access' => $this->access,
                'actions' => $this->actionsCsv,
                'active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            DB::table('modules')->where('id', $moduleId)->update([
                'access' => $this->access,
                'actions' => $this->actionsCsv,
                'updated_at' => $now,
            ]);
        }

        foreach (explode(',', $this->actionsCsv) as $action) {
            $slug = strtolower($action).'_'.Str::snake($this->moduleName);
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

            if (! DB::table('permission_role')->where([
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

    public function down(): void
    {
        $moduleId = DB::table('modules')->where('name', $this->moduleName)->value('id');

        if (! $moduleId) {
            return;
        }

        $permissionIds = DB::table('permissions')->where('module_id', $moduleId)->pluck('id');

        DB::table('permission_role')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')->whereIn('id', $permissionIds)->delete();
        DB::table('modules')->where('id', $moduleId)->delete();
    }
};
