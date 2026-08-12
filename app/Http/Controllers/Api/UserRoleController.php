<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;

class UserRoleController extends Controller
{
    /**
     * Assign a role to a user.
     */
    public function store(
        Request $request,
        User $user
    ) {
        $request->validate([
            'role_uuid' => [
                'required',
                'uuid',
                'exists:roles,uuid'
            ],
        ]);

        $role = Role::where(
            'uuid',
            $request->role_uuid
        )->firstOrFail();

        $user->roles()
            ->syncWithoutDetaching([
                $role->id
            ]);

        $user->clearRbacCache();

        return $user->load('roles');
    }

    /**
     * Remove a role from a user.
     */
    public function destroy(
        User $user,
        Role $role
    ) {
        $user->roles()->detach($role->id);
        $user->clearRbacCache();

        return response()->json([
            'message' => 'Role removed.'
        ]);
    }
}