<?php
namespace App\Http\Controllers;

use App\Models\PropertyCategory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class PropertyCategoryController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => PropertyCategory::all()]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:property_categories,name',
        ]);
        
        $validated['slug'] = Str::slug($validated['name']);

        $category = PropertyCategory::create($validated);
        return response()->json(['data' => $category], 201);
    }

    public function show(PropertyCategory $propertyCategory): JsonResponse
    {
        return response()->json(['data' => $propertyCategory]);
    }
    
    // update and destroy methods omitted as per routes/api.php, but can be added if needed
}