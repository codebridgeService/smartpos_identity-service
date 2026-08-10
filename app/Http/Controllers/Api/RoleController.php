<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        return Role::query()
            ->with('permissions')
            ->when(
                $request->business_uuid,
                fn ($q, $uuid) =>
                    $q->where('business_uuid', $uuid)
            )
            ->paginate(20);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'business_uuid' => [
                'nullable',
                'uuid'
            ],
            'name' => [
                'required',
                'string',
                'max:100'
            ],
            'code' => [
                'required',
                'string',
                'max:100'
            ],
            'is_system' => [
                'boolean'
            ],
        ]);

        return Role::create($data);
    }

    public function show(Role $role)
    {
        return $role->load('permissions');
    }

    public function update(
        Request $request,
        Role $role
    ) {
        $role->update(
            $request->validate([
                'name' => [
                    'sometimes',
                    'string',
                    'max:100'
                ],
                'code' => [
                    'sometimes',
                    'string',
                    'max:100'
                ],
            ])
        );

        return $role;
    }

    public function destroy(Role $role)
    {
        $role->delete();

        return response()->json([
            'message' => 'Role deleted.'
        ]);
    }

    public function syncPermissions(
        Request $request,
        Role $role
    ) {
        $data = $request->validate([
            'permission_uuids' => [
                'required',
                'array'
            ],
            'permission_uuids.*' => [
                'uuid',
                'exists:permissions,uuid'
            ],
        ]);

        $ids = Permission::query()
            ->whereIn(
                'uuid',
                $data['permission_uuids']
            )
            ->pluck('id');

        $role->permissions()->sync($ids);

        return $role->load('permissions');
    }
}