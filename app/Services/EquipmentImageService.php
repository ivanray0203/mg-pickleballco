<?php

namespace App\Services;

use App\Models\Equipment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Centralises equipment image storage so the rest of the application never
 * talks to a specific disk directly. Mirrors {@see CourtImageService}.
 */
class EquipmentImageService
{
    /**
     * The directory (within the disk) that equipment images live in.
     */
    private const DIRECTORY = 'equipment';

    /**
     * Store a newly uploaded image and return its disk-relative path.
     */
    public function store(UploadedFile $image): string
    {
        $path = $image->store(self::DIRECTORY, Equipment::IMAGE_DISK);

        if ($path === false) {
            throw new RuntimeException('Failed to store the equipment image.');
        }

        return $path;
    }

    /**
     * Delete an image by its disk-relative path, if present.
     */
    public function delete(?string $path): void
    {
        if ($path && Storage::disk(Equipment::IMAGE_DISK)->exists($path)) {
            Storage::disk(Equipment::IMAGE_DISK)->delete($path);
        }
    }

    /**
     * Replace an existing image with a new upload, cleaning up the old file.
     */
    public function replace(?string $currentPath, UploadedFile $image): string
    {
        $newPath = $this->store($image);

        $this->delete($currentPath);

        return $newPath;
    }
}
