<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class RbacCacheService
{
    /**
     * Cache TTL in seconds (1 hour).
     */
    public const CACHE_TTL = 3600;

    /**
     * Get user's permission codes using Redis cache.
     */
    public static function getUserPermissionCodes(User $user): array
    {
        $cacheKey = "user:{$user->uuid}:permission_codes";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($user) {
            return $user->allPermissions()
                ->pluck('code')
                ->unique()
                ->values()
                ->all();
        });
    } 

    /**
     * Get user's role codes using Redis cache.
     */
    public static function getUserRoleCodes(User $user): array
    {
        $cacheKey = "user:{$user->uuid}:role_codes";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($user) {
            $user->loadMissing('roles');

            return $user->roles
                ->pluck('code')
                ->unique()
                ->values()
                ->all();
        });
    }

    /**
     * Check if user has given permission(s) using cached permission list.
     */
    public static function hasPermission(User $user, string|array $permissions): bool
    {
        $requiredCodes = is_array($permissions)
            ? $permissions
            : explode(',', $permissions);

        $requiredCodes = array_map('trim', $requiredCodes);
        $userCodes = self::getUserPermissionCodes($user);

        foreach ($requiredCodes as $code) {
            if (in_array($code, $userCodes, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user has given role(s) using cached role list.
     */
    public static function hasRole(User $user, string|array $roles): bool
    {
        $requiredRoles = is_array($roles)
            ? $roles
            : explode(',', $roles);

        $requiredRoles = array_map('trim', $requiredRoles);
        $userRoles = self::getUserRoleCodes($user);

        foreach ($requiredRoles as $code) {
            if (in_array($code, $userRoles, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Invalidate cached RBAC permissions and roles for a specific user.
     */
    public static function forgetUserCache(User $user): void
    {
        Cache::forget("user:{$user->uuid}:permission_codes");
        Cache::forget("user:{$user->uuid}:role_codes");
    }

    /**
     * Invalidate cached RBAC permissions for all users assigned to a specific role.
     */
    public static function forgetRoleUsersCache(Role $role): void
    {
        $role->loadMissing('users');

        foreach ($role->users as $user) {
            self::forgetUserCache($user);
        }
    }
}
