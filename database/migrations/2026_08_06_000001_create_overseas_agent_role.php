<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Create the "Overseas Agent" role and assign tailored permissions for viewing, translating,
     * attaching files, updating status, and commenting.
     */
    public function up(): void
    {
        $role = Role::firstOrCreate(['name' => 'Overseas Agent']);

        $permissionNames = [
            'view_request',
            'view_attachments',
            'manage_attachments',
            'update_status',
            'view_all_comments',
        ];

        $permissionIds = Permission::whereIn('name', $permissionNames)->pluck('id')->all();

        if (!empty($permissionIds)) {
            $role->permissions()->syncWithoutDetaching($permissionIds);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $role = Role::where('name', 'Overseas Agent')->first();
        if ($role) {
            $role->permissions()->detach();
            $role->delete();
        }
    }
};
