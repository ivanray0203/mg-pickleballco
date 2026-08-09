<?php

namespace App\Http\Controllers\Client;

use App\Enums\CourtType;
use App\Http\Controllers\Controller;
use App\Http\Resources\CourtResource;
use App\Models\Court;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;
use Inertia\Inertia;
use Inertia\Response;

class CourtController extends Controller
{
    /**
     * Browse bookable (active) courts, optionally filtered by type or name.
     */
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'type' => ['nullable', new Enum(CourtType::class)],
            'search' => ['nullable', 'string', 'max:255'],
        ]);

        $type = $filters['type'] ?? null;
        $search = $filters['search'] ?? null;

        $courts = Court::query()
            ->active()
            ->when($type, fn ($query, $value) => $query->where('type', $value))
            ->when($search, fn ($query, $value) => $query->where('name', 'like', "%{$value}%"))
            ->orderBy('name')
            ->paginate(9)
            ->withQueryString();

        return Inertia::render('client/courts', [
            'courts' => CourtResource::collection($courts),
            'filters' => ['type' => $type, 'search' => $search],
            'courtTypes' => CourtType::options(),
        ]);
    }

    /**
     * Show a single bookable court. Inactive courts are never accessible,
     * even by manually constructing the URL.
     */
    public function show(Court $court): Response
    {
        abort_unless($court->is_active, 404);

        return Inertia::render('client/court-detail', [
            'court' => new CourtResource($court),
        ]);
    }
}
