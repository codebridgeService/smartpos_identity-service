<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class AvatarService
{
    /**
     * Process and store avatar image file as WebP format.
     * Accepts JPEG, PNG, GIF, or WebP uploaded files.
     */
    public function uploadAvatar(
        User $user,
        UploadedFile $file,
        string $disk = 'public',
        int $quality = 80
    ): string {
        $this->deleteAvatarFile($user, $disk);

        $content = file_get_contents($file->getRealPath());
        if ($content === false) {
            throw new RuntimeException('Failed to read uploaded image content.');
        }

        $image = @imagecreatefromstring($content);
        if ($image === false) {
            throw new RuntimeException('Invalid image payload or unsupported format.');
        }

        imagealphablending($image, true);
        imagesavealpha($image, true);

        ob_start();
        $success = imagewebp($image, null, $quality);
        $webpContent = ob_get_clean();

        imagedestroy($image);

        if (!$success || $webpContent === false) {
            throw new RuntimeException('Failed to process image into WebP format.');
        }

        $path = 'avatars/' . Str::uuid() . '.webp';
        Storage::disk($disk)->put($path, $webpContent);

        $user->update([
            'avatar' => $path,
        ]);

        return $path;
    }

    /**
     * Remove existing avatar file and clear database attribute.
     */
    public function removeAvatar(User $user, string $disk = 'public'): bool
    {
        $this->deleteAvatarFile($user, $disk);

        $user->update([
            'avatar' => null,
        ]);

        return true;
    }

    /**
     * Helper to delete physical avatar file if exists.
     */
    protected function deleteAvatarFile(User $user, string $disk = 'public'): void
    {
        if ($user->avatar && Storage::disk($disk)->exists($user->avatar)) {
            Storage::disk($disk)->delete($user->avatar);
        }
    }
}
