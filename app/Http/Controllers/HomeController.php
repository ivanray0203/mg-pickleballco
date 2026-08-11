<?php

namespace App\Http\Controllers;

use App\Http\Resources\EquipmentResource;
use App\Models\Equipment;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    /**
     * Number of rentals shown in the landing page's equipment preview.
     */
    private const EQUIPMENT_PREVIEW_LIMIT = 4;

    /**
     * Show the public landing page with a preview of the rentals customers can
     * add to a booking. Only active equipment is advertised.
     */
    public function __invoke(): Response
    {
        $equipment = Equipment::query()
            ->active()
            ->orderBy('hourly_rate')
            ->orderBy('name')
            ->limit(self::EQUIPMENT_PREVIEW_LIMIT)
            ->get();

        return Inertia::render('welcome', [
            'equipment' => EquipmentResource::collection($equipment),
        ]);
    }
}
