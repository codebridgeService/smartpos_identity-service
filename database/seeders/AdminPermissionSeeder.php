<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class AdminPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::query()
            ->where('code', 'admin')
            ->firstOrFail();

        $permissionIds = Permission::query()
            ->pluck('id');

        $adminRole->permissions()->sync($permissionIds);
    }
}