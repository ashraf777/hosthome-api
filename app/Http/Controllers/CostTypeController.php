<?php
namespace App\Http\Controllers;

use App\Models\CostType;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class CostTypeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        // CORRECT: Permission Check
        if (!$request->user()->canPermission('cost-type:view')) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }
        return response()->json(['data' => CostType::all()]);
    }

    public function store(Request $request): JsonResponse
    {
        // CORRECT: Permission Check
        if (!$request->user()->canPermission('cost-type:create')) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:50|unique:cost_types,name',
        ]);
        
        $validated['slug'] = Str::slug($validated['name']);

        $costType = CostType::create($validated);
        return response()->json(['data' => $costType], 201);
    }
    
    public function show(Request $request, CostType $costType): JsonResponse
    {
        // CORRECT: Permission Check
        if (!$request->user()->canPermission('cost-type:view')) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }
        return response()->json(['data' => $costType]);
    }
    
    // update and destroy methods omitted as per routes/api.php, but can be added if needed
}