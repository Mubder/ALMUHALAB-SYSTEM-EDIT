<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $permissions = [
            'create_request',
            'view_request',
            'edit_request',
            'delete_request',
            'view_trash',
            'restore_request',
            'force_delete_request',
            'manage_users',
            'manage_followups',
            'view_audit_log',
            'update_status',
            'manage_services',
            'manage_service_catalog',
            'manage_attachments',
            'view_attachments',
            'manage_pages',
            'transition_stage',
            'force_transition',
            'manage_assignments',
            'view_all_comments',
        ];

        $created = collect();
        foreach ($permissions as $name) {
            $created->push(Permission::firstOrCreate(['name' => $name]));
        }

        // Assign permissions to Admin (all) and User (create_request, view_request)
        $admin = Role::where('name', 'Admin')->orWhere('name', 'admin')->first();
        if ($admin) {
            $admin->permissions()->sync($created->pluck('id')->all());
        }

        $user = Role::where('name', 'User')->orWhere('name', 'user')->first();
        if ($user) {
            $userPerms = Permission::whereIn('name', ['create_request', 'view_request', 'delete_request'])->pluck('id')->all();
            $user->permissions()->sync($userPerms);
        }

    }
}
