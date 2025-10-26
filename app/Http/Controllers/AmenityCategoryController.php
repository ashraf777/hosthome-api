<?php
namespace App\Http\Controllers;

use App\Models\AmenityCategory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AmenityCategoryController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => AmenityCategory::orderBy('sort_order')->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:amenity_categories,name',
            'icon' => 'nullable|string|max:50',
            'sort_order' => 'nullable|integer',
        ]);

        $category = AmenityCategory::create($validated);
        return response()->json(['data' => $category], 201);
    }

    public function show(AmenityCategory $amenityCategory): JsonResponse
    {
        return response()->json(['data' => $amenityCategory]);
    }
    
    // update and destroy methods omitted as per routes/api.php, but can be added if needed
}