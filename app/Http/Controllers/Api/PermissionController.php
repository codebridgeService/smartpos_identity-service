<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    public function index()
    {
        return Permission::query()
            ->orderBy('module')
            ->orderBy('code')
            ->paginate(50);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => [
                'required',
                'string',
                'max:150',
                'unique:permissions,code'
            ],
            'name' => [
                'required',
                'string',
                'max:150'
            ],
            'module' => [
                'nullable',
                'string',
                'max:100'
            ],
            'description' => [
                'nullable',
                'string',
                'max:255'
            ],
        ]);

        return Permission::create($data);
    }

    public function show(Permission $permission)
    {
        return $permission;
    }

    public function update(
        Request $request,
        Permission $permission
    ) {
        $permission->update(
            $request->validate([
                'name' => [
                    'sometimes',
                    'string',
                    'max:150'
                ],
                'module' => [
                    'sometimes',
                    'nullable',
                    'string',
                    'max:100'
                ],
                'description' => [
                    'sometimes',
                    'nullable',
                    'string',
                    'max:255'
                ],
            ])
        );

        return $permission;
    }

    public function destroy(Permission $permission)
    {
        $permission->delete();

        return response()->json([
            'message' => 'Permission deleted.'
        ]);
    }
}