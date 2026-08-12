<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RbacMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_request_to_permission_guarded_endpoint_returns_401()
    {
        $response = $this->getJson('/api/v1/users');

        $response->assertStatus(401);
    }

    public function test_user_without_permission_receives_403_forbidden()
    {
        $user = User::factory()->create(['status' => 'active']);
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/users');

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Forbidden. Required permission missing.',
            ]);
    }

    public function test_user_with_permission_can_access_guarded_endpoint()
    {
        $user = User::factory()->create(['status' => 'active']);
        $role = Role::create([
            'name' => 'Admin',
            'code' => 'admin',
        ]);
        $permission = Permission::create([
            'code' => 'users.view',
            'name' => 'View Users',
            'module' => 'users',
        ]);

        $role->permissions()->attach($permission->id);
        $user->roles()->attach($role->id);

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/users');

        $response->assertStatus(200);
    }

    public function test_user_has_role_and_has_permission_helpers()
    {
        $user = User::factory()->create();
        $role = Role::create([
            'name' => 'Manager',
            'code' => 'manager',
        ]);
        $permission = Permission::create([
            'code' => 'reports.view',
            'name' => 'View Reports',
            'module' => 'reports',
        ]);

        $role->permissions()->attach($permission->id);
        $user->roles()->attach($role->id);

        $this->assertTrue($user->hasRole('manager'));
        $this->assertFalse($user->hasRole('admin'));

        $this->assertTrue($user->hasPermission('reports.view'));
        $this->assertFalse($user->hasPermission('users.manage'));
    }
}
