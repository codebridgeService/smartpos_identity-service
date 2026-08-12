<?php

namespace Tests\Unit;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\RbacCacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class RbacCacheServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_rbac_permissions_and_roles_are_cached_in_redis()
    {
        $user = User::factory()->create(['status' => 'active']);
        $role = Role::create(['name' => 'Manager', 'code' => 'manager']);
        $permission = Permission::create(['name' => 'Manage Products', 'code' => 'products.manage', 'module' => 'products']);

        $role->permissions()->attach($permission->id);
        $user->roles()->attach($role->id);

        // First call populates cache
        $this->assertTrue($user->hasPermission('products.manage'));
        $this->assertTrue($user->hasRole('manager'));

        // Verify Redis cache keys exist
        $this->assertTrue(Cache::has("user:{$user->uuid}:permission_codes"));
        $this->assertTrue(Cache::has("user:{$user->uuid}:role_codes"));

        // Verify cached values
        $this->assertEquals(['products.manage'], Cache::get("user:{$user->uuid}:permission_codes"));
        $this->assertEquals(['manager'], Cache::get("user:{$user->uuid}:role_codes"));

        // Clear cache and verify key removal
        $user->clearRbacCache();
        $this->assertFalse(Cache::has("user:{$user->uuid}:permission_codes"));
        $this->assertFalse(Cache::has("user:{$user->uuid}:role_codes"));
    }
}
