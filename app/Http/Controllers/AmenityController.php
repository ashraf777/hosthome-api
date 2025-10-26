<?php
namespace App\Http\Controllers;

use App\Models\Amenity;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AmenityController extends Controller
{
    public function index(): JsonResponse
    {
        $amenities = Amenity::with('category')->get();
        return response()->json(['data' => $amenities]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:amenities,name',
            'amenity_category_id' => 'required|exists:amenity_categories,id',
            'icon' => 'nullable|string|max:50',
        ]);

        $amenity = Amenity::create($validated);
        return response()->json(['data' => $amenity], 201);
    }

    public function show(Amenity $amenity): JsonResponse
    {
        $amenity->load('category');
        return response()->json(['data' => $amenity]);
    }

    public function update(Request $request, Amenity $amenity): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:100|unique:amenities,name,' . $amenity->id,
            'amenity_category_id' => 'sometimes|exists:amenity_categories,id',
            'icon' => 'nullable|string|max:50',
        ]);
        
        $amenity->update($validated);
        return response()->json(['data' => $amenity]);
    }

    public function destroy(Amenity $amenity): JsonResponse
    {
        $amenity->delete();
        return response()->json(null, 204);
    }
}