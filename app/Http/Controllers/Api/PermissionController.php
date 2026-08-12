<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PermissionController extends Controller
{
    /**
     * List all permissions ordered by module and code.
     */
    public function index()
    {
        return Permission::query()
            ->orderBy('module')
            ->orderBy('code')
            ->paginate(50);
    }
    
    /**
     * Create new permissions in batch.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            '*.code' => [
                'required',
                'string',
                'max:150',
                'distinct',
                'unique:permissions,code',
            ],
    
            '*.name' => [
                'required',
                'string',
                'max:150',
            ],
    
            '*.module' => [
                'nullable',
                'string',
                'max:100',
            ],
    
            '*.description' => [
                'nullable',
                'string',
                'max:255',
            ],
        ]);
    
        $permissions = DB::transaction(function () use ($data) {
    
            return collect($data)->map(function ($permission) {
                return Permission::create([
                    'code' => strtolower($permission['code']),
                    'name' => $permission['name'],
                    'module' => isset($permission['module'])
                        ? strtolower($permission['module'])
                        : null,
                    'description' => $permission['description'] ?? null,
                ]);
            });
        });
    
        return response()->json([
            'message' => 'Permissions created successfully.',
            'count' => $permissions->count(),
            'data' => $permissions,
        ], 201);
    }

    /**
     * Get permission details by model binding.
     */
    public function show(Permission $permission)
    {
        return $permission;
    }

    /**
     * Update an existing permission's details.
     */
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

    /**
     * Delete a permission.
     */
    public function destroy(Permission $permission)
    {
        $permission->delete();

        return response()->json([
            'message' => 'Permission deleted.'
        ]);
    }
}