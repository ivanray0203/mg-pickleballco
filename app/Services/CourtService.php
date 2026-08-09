<?php

namespace App\Services;

use App\Models\Court;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class CourtService
{
    public function __construct(
        private CourtImageService $images,
        private CourtScheduleService $schedules,
    ) {}

    /**
     * Create a new court from validated data, seeding a default weekly
     * schedule from its base operating hours.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?UploadedFile $image = null): Court
    {
        $attributes = $this->normalize($data);
        $attributes['slug'] = $this->uniqueSlug($data['name']);

        if ($image) {
            $attributes['image_path'] = $this->images->store($image);
        }

        $court = Court::create($attributes);

        $this->schedules->getOrCreateWeek($court);

        return $court;
    }

    /**
     * Update an existing court from validated data.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Court $court, array $data, ?UploadedFile $image = null): Court
    {
        $attributes = $this->normalize($data);

        if ($court->name !== $data['name']) {
            $attributes['slug'] = $this->uniqueSlug($data['name'], $court->id);
        }

        if ($image) {
            $attributes['image_path'] = $this->images->replace($court->image_path, $image);
        }

        $court->update($attributes);

        return $court;
    }

    /**
     * Toggle a court's active status without touching its bookings.
     */
    public function toggleStatus(Court $court): Court
    {
        $court->update(['is_active' => ! $court->is_active]);

        return $court;
    }

    /**
     * Map validated form data to persistable attributes, converting the
     * dollar-based rate into integer cents.
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
            'opening_time' => $data['opening_time'],
            'closing_time' => $data['closing_time'],
            'is_active' => (bool) $data['is_active'],
        ];
    }

    /**
     * Generate a unique slug for a court name.
     */
    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $suffix = 1;

        while (Court::query()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists()
        ) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
