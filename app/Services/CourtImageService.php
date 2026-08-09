<?php

namespace App\Services;

use App\Models\Court;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Centralises court image storage so the rest of the application never talks
 * to a specific disk directly. Swapping to S3 (or any other driver) is a
 * matter of changing {@see Court::IMAGE_DISK}.
 */
class CourtImageService
{
    /**
     * The directory (within the disk) that court images live in.
     */
    private const DIRECTORY = 'courts';

    /**
     * Store a newly uploaded image and return its disk-relative path.
     */
    public function store(UploadedFile $image): string
    {
        $path = $image->store(self::DIRECTORY, Court::IMAGE_DISK);

        if ($path === false) {
            throw new RuntimeException('Failed to store the court image.');
        }

        return $path;
    }

    /**
     * Delete an image by its disk-relative path, if present.
     */
    public function delete(?string $path): void
    {
        if ($path && Storage::disk(Court::IMAGE_DISK)->exists($path)) {
            Storage::disk(Court::IMAGE_DISK)->delete($path);
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
