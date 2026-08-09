<?php

namespace App\Services;

use App\Models\Equipment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class EquipmentService
{
    public function __construct(
        private EquipmentImageService $images,
    ) {}

    /**
     * Create a new equipment item from validated data.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?UploadedFile $image = null): Equipment
    {
        $attributes = $this->normalize($data);
        $attributes['slug'] = $this->uniqueSlug($data['name']);

        if ($image) {
            $attributes['image_path'] = $this->images->store($image);
        }

        return Equipment::create($attributes);
    }

    /**
     * Update an existing equipment item from validated data.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Equipment $equipment, array $data, ?UploadedFile $image = null): Equipment
    {
        $attributes = $this->normalize($data);

        if ($equipment->name !== $data['name']) {
            $attributes['slug'] = $this->uniqueSlug($data['name'], $equipment->id);
        }

        if ($image) {
            $attributes['image_path'] = $this->images->replace($equipment->image_path, $image);
        }

        $equipment->update($attributes);

        return $equipment;
    }

    /**
     * Toggle an equipment item's active status.
     */
    public function toggleStatus(Equipment $equipment): Equipment
    {
        $equipment->update(['is_active' => ! $equipment->is_active]);

        return $equipment;
    }

    /**
     * Map validated form data to persistable attributes, converting the
     * peso-based rate into integer cents.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalize(array $data): array
    {
        return [
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'type' => $data['type'],
            'hourly_rate' => (int) round(((float) $data['hourly_rate']) * 100),
            'total_quantity' => (int) $data['total_quantity'],
            'is_active' => (bool) $data['is_active'],
        ];
    }

    /**
     * Generate a unique slug for an equipment name.
     */
    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $suffix = 1;

        while (Equipment::query()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists()
        ) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
