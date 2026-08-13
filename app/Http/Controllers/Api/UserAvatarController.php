<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AvatarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UserAvatarController extends Controller
{
    public function __construct(
        protected AvatarService $avatarService
    ) {}

    /**
     * Upload and convert user avatar to WebP format.
     */
    public function upload(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'avatar' => [
                'required',
                'file',
                'image',
                'mimes:jpeg,jpg,png,webp',
                'max:5120',
            ],
        ]);

        $path = $this->avatarService->uploadAvatar(
            $user,
            $request->file('avatar')
        );

        return response()->json([
            'message' => 'Avatar updated successfully.',
            'data' => [
                'avatar' => $path,
                'avatar_url' => Storage::disk('public')->url($path),
            ],
        ]);
    }

    /**
     * Remove user avatar.
     */
    public function destroy(User $user): JsonResponse
    {
        $this->avatarService->removeAvatar($user);

        return response()->json([
            'message' => 'Avatar removed successfully.',
            'data' => [
                'avatar' => null,
                'avatar_url' => null,
            ],
        ]);
    }
}
