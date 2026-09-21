<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeliveryZone;
use Illuminate\Http\Request;

class DeliveryZoneController extends Controller
{
    public function index()
    {
        return response()->json(
            DeliveryZone::where('is_active', true)->orderBy('name')->get()
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'customer_fee' => 'required|numeric|min:0',
            'courier_fee' => 'required|numeric|min:0',
        ]);

        $zone = DeliveryZone::create($validated);
        return response()->json($zone, 201);
    }
}