<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\PropertyOwner;
use App\Http\Resources\PropertyResource;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PropertyController extends Controller
{
    public function index(Request $request)
    {
        // if (!$request->user()->canPermission('property:view')) {
        //     return response()->json(['message' => 'This action is unauthorized.'], 403);
        // }

        $properties = Property::with('owner', 'hostingCompany', 'propertyType', 'roomTypes', 'amenities')
            ->where('hosting_company_id', $request->user()->hosting_company_id)
            ->paginate(15);

        return PropertyResource::collection($properties);
    }

    public function store(Request $request)
    {
        // if (!$request->user()->canPermission('property:create')) {
        //     return response()->json(['message' => 'This action is unauthorized.'], 403);
        // }

        $validated = $request->validate([
            'property_owner_id' => 'nullable|exists:property_owners,id',
            'name' => 'required|string|max:255',
            'address_line_1' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:50',
            'zip_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:50',
            'timezone' => 'nullable|string|max:50',
            'property_type_ref_id' => 'nullable|exists:property_references,id',
            'listing_status' => ['sometimes', 'required', Rule::in(['draft', 'active', 'archived'])],
            'status' => 'sometimes|required|boolean',
            'check_in_time' => 'nullable|string|max:50',
            'check_out_time' => 'nullable|string|max:50',
            'min_nights' => 'nullable|integer|min:0',
            'max_nights' => 'nullable|integer|min:0',
        ]);

        // Securely set the hosting company ID from the authenticated user
        $validated['hosting_company_id'] = $request->user()->hosting_company_id;

        // Tenancy check on the related property owner
        // if (isset($validated['property_owner_id'])) {
        //     $owner = PropertyOwner::find($validated['property_owner_id']);
        //     if (!$owner || $owner->hosting_company_id !== $validated['hosting_company_id']) {
        //         return response()->json(['message' => 'The selected property owner is invalid.'], 422);
        //     }
        // }

        $property = Property::create($validated);
        $property->load('owner', 'hostingCompany', 'propertyType', 'roomTypes', 'amenities');

        return new PropertyResource($property);
    }

    public function show(Request $request, Property $property)
    {
        if ($property->hosting_company_id !== $request->user()->hosting_company_id) {
            return response()->json(['message' => 'Not Found'], 404);
        }

        // if (!$request->user()->canPermission('property:view')) {
        //     return response()->json(['message' => 'This action is unauthorized.'], 403);
        // }
        
        $property->load('owner', 'hostingCompany', 'propertyType', 'roomTypes', 'amenities');

        return new PropertyResource($property);
    }

    public function update(Request $request, Property $property)
    {
        if ($property->hosting_company_id !== $request->user()->hosting_company_id) {
            return response()->json(['message' => 'Not Found'], 404);
        }

        // if (!$request->user()->canPermission('property:update')) {
        //     return response()->json(['message' => 'This action is unauthorized.'], 403);
        // }

        $validated = $request->validate([
            'property_owner_id' => 'nullable|exists:property_owners,id',
            'name' => 'sometimes|required|string|max:255',
            'address_line_1' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:50',
            'zip_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:50',
            'timezone' => 'nullable|string|max:50',
            'property_type_ref_id' => 'nullable|exists:property_references,id',
            'listing_status' => ['sometimes', 'required', Rule::in(['draft', 'active', 'archived'])],
            'status' => 'sometimes|required|boolean',
            'check_in_time' => 'nullable|string|max:50',
            'check_out_time' => 'nullable|string|max:50',
            'min_nights' => 'nullable|integer|min:0',
            'max_nights' => 'nullable|integer|min:0',
        ]);
        
        // Tenancy check on the related property owner if it is being changed
        // if (isset($validated['property_owner_id'])) {
        //     $owner = PropertyOwner::find($validated['property_owner_id']);
        //     if (!$owner || $owner->hosting_company_id !== $request->user()->hosting_company_id) {
        //         return response()->json(['message' => 'The selected property owner is invalid.'], 422);
        //     }
        // }

        $property->update($validated);
        $property->load('owner', 'hostingCompany', 'propertyType', 'roomTypes', 'amenities');

        return new PropertyResource($property);
    }

    public function destroy(Request $request, Property $property)
    {
        if ($property->hosting_company_id !== $request->user()->hosting_company_id) {
            return response()->json(['message' => 'Not Found'], 404);
        }

        // if (!$request->user()->canPermission('property:delete')) {
        //     return response()->json(['message' => 'This action is unauthorized.'], 403);
        // }

        $property->delete();

        return response()->noContent();
    }
}
