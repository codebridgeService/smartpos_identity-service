<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserAvatarTest extends TestCase
{
    use RefreshDatabase;

    protected function createAuthorizedUser(string $permissionCode = 'users.update'): array
    {
        $user = User::factory()->create(['status' => 'active']);
        $role = Role::create(['name' => 'Admin', 'code' => 'admin_' . rand(1000, 9999)]);
        $permission = Permission::firstOrCreate(
            ['code' => $permissionCode],
            ['name' => 'Update Users', 'module' => 'users']
        );

        $role->permissions()->attach($permission->id);
        $user->roles()->attach($role->id);

        $token = auth('api')->login($user);

        return [$user, $token];
    }

    public function test_user_avatar_upload_converts_jpeg_to_webp_and_saves()
    {
        Storage::fake('public');

        [$user, $token] = $this->createAuthorizedUser('users.update');

        $file = UploadedFile::fake()->image('test_avatar.jpg', 200, 200);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/v1/users/{$user->uuid}/avatar", [
                'avatar' => $file,
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => ['avatar', 'avatar_url'],
            ]);

        $user->refresh();
        $this->assertNotNull($user->avatar);
        $this->assertStringEndsWith('.webp', $user->avatar);

        Storage::disk('public')->assertExists($user->avatar);

        // Verify the stored file is actually a valid WebP image
        $storedContent = Storage::disk('public')->get($user->avatar);
        $image = @imagecreatefromstring($storedContent);
        $this->assertNotFalse($image);
        imagedestroy($image);
    }

    public function test_user_avatar_upload_converts_png_to_webp_and_saves()
    {
        Storage::fake('public');

        [$user, $token] = $this->createAuthorizedUser('users.update');

        $file = UploadedFile::fake()->image('test_avatar.png', 150, 150);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/v1/users/{$user->uuid}/avatar", [
                'avatar' => $file,
            ]);

        $response->assertStatus(200);

        $user->refresh();
        $this->assertStringEndsWith('.webp', $user->avatar);
        Storage::disk('public')->assertExists($user->avatar);
    }

    public function test_user_avatar_upload_fails_for_non_image_files()
    {
        Storage::fake('public');

        [$user, $token] = $this->createAuthorizedUser('users.update');

        $file = UploadedFile::fake()->create('document.pdf', 500, 'application/pdf');

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/v1/users/{$user->uuid}/avatar", [
                'avatar' => $file,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['avatar']);
    }

    public function test_user_avatar_deletion_removes_file_and_clears_attribute()
    {
        Storage::fake('public');

        [$user, $token] = $this->createAuthorizedUser('users.update');

        $file = UploadedFile::fake()->image('avatar.jpg', 100, 100);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/v1/users/{$user->uuid}/avatar", ['avatar' => $file]);

        $user->refresh();
        $avatarPath = $user->avatar;
        Storage::disk('public')->assertExists($avatarPath);

        $deleteResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson("/api/v1/users/{$user->uuid}/avatar");

        $deleteResponse->assertStatus(200)
            ->assertJson([
                'message' => 'Avatar removed successfully.',
                'data' => [
                    'avatar' => null,
                    'avatar_url' => null,
                ],
            ]);

        $user->refresh();
        $this->assertNull($user->avatar);
        Storage::disk('public')->assertMissing($avatarPath);
    }

    public function test_avatar_upload_requires_users_update_permission()
    {
        Storage::fake('public');

        $user = User::factory()->create(['status' => 'active']);
        $token = auth('api')->login($user);

        $file = UploadedFile::fake()->image('test.jpg', 100, 100);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/v1/users/{$user->uuid}/avatar", [
                'avatar' => $file,
            ]);

        $response->assertStatus(403);
    }
}
