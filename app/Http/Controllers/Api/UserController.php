<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index()
    {
        return User::query()
            ->with('roles')
            ->latest()
            ->paginate(20);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:150'
            ],

            'username' => [
                'nullable',
                'string',
                'max:100',
                'unique:users,username'
            ],

            'email' => [
                'nullable',
                'email',
                'max:150',
                'unique:users,email'
            ],

            'phone' => [
                'nullable',
                'string',
                'max:30',
                'unique:users,phone'
            ],

            'password' => [
                'nullable',
                Password::min(8)
            ],

            'status' => [
                'nullable',
                Rule::in([
                    'active',
                    'inactive',
                    'blocked',
                ])
            ],
        ]);

        $user = User::create($data);

        return response()->json([
            'message' => 'User created.',
            'data' => $user,
        ], 201);
    }

    public function show(User $user)
    {
        return $user->load([
            'roles.permissions',
            'devices',
        ]);
    }

    public function update(
        Request $request,
        User $user
    ) {
        $data = $request->validate([
            'name' => [
                'sometimes',
                'string',
                'max:150'
            ],

            'username' => [
                'sometimes',
                'nullable',
                Rule::unique('users', 'username')
                    ->ignore($user->id),
            ],

            'email' => [
                'sometimes',
                'nullable',
                'email',
                Rule::unique('users', 'email')
                    ->ignore($user->id),
            ],

            'phone' => [
                'sometimes',
                'nullable',
                Rule::unique('users', 'phone')
                    ->ignore($user->id),
            ],

            'password' => [
                'sometimes',
                'nullable',
                Password::min(8),
            ],

            'status' => [
                'sometimes',
                Rule::in([
                    'active',
                    'inactive',
                    'blocked'
                ]),
            ],
        ]);

        $user->update($data);

        return response()->json([
            'message' => 'User updated.',
            'data' => $user->fresh(),
        ]);
    }

    public function destroy(User $user)
    {
        $user->delete();

        return response()->json([
            'message' => 'User deleted.'
        ]);
    }
}