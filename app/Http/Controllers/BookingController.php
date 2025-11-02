<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Http\Resources\BookingResource;
use Illuminate\Http\Request;

// CORRECT: Extends the base Controller
class BookingController extends Controller
{
    public function index(Request $request)
    {
        // CORRECT: Using the application's canPermission() method
        if (!$request->user()->canPermission('booking:view')) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        $query = Booking::query()->with(['guest', 'propertyUnit']);

        // CORRECT: Tenancy Check to ensure users only see their own company's bookings
        $query->whereHas('propertyUnit.property', function ($q) use ($request) {
            $q->where('hosting_company_id', $request->user()->hosting_company_id);
        });

        // Filtering Logic from your original code
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->has('property_id')) {
            $propertyId = $request->input('property_id');
            $query->whereHas('propertyUnit', function ($q) use ($propertyId) {
                $q->where('property_id', $propertyId);
            });
        }

        return BookingResource::collection($query->paginate(15));
    }

    public function store(Request $request)
    {
        if (!$request->user()->canPermission('booking:create')) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        $validated = $request->validate([
            'property_unit_id' => 'required|exists:property_units,id',
            'guest_id' => 'required|exists:guests,id',
            'check_in_date' => 'required|date',
            'check_out_date' => 'required|date|after_or_equal:check_in_date',
            'total_price' => 'required|numeric|min:0',
            'status' => 'required|string|in:confirmed,cancelled,pending',
        ]);
        
        // CORRECT: Tenancy check before creation
        $unit = \App\Models\PropertyUnit::find($validated['property_unit_id']);
        if ($unit->property->hosting_company_id !== $request->user()->hosting_company_id) {
             return response()->json(['message' => 'Invalid property unit specified.'], 422);
        }

        $booking = Booking::create($validated);
        return new BookingResource($booking);
    }

    public function show(Request $request, Booking $booking)
    {
        // CORRECT: Tenancy Check on the specific resource
        if ($booking->propertyUnit->property->hosting_company_id !== $request->user()->hosting_company_id) {
            return response()->json(['message' => 'Not Found'], 404);
        }

        if (!$request->user()->canPermission('booking:view')) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        return new BookingResource($booking->load('guest'));
    }

    public function update(Request $request, Booking $booking)
    {
        if ($booking->propertyUnit->property->hosting_company_id !== $request->user()->hosting_company_id) {
            return response()->json(['message' => 'Not Found'], 404);
        }

        if (!$request->user()->canPermission('booking:update')) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        $validated = $request->validate([
            'guest_id' => 'sometimes|exists:guests,id',
            'check_in_date' => 'sometimes|date',
            'check_out_date' => 'sometimes|date|after_or_equal:check_in_date',
            'total_price' => 'sometimes|numeric|min:0',
            'status' => 'sometimes|string|in:confirmed,cancelled,pending',
        ]);

        $booking->update($validated);
        return new BookingResource($booking);
    }

    public function destroy(Request $request, Booking $booking)
    {
        if ($booking->propertyUnit->property->hosting_company_id !== $request->user()->hosting_company_id) {
            return response()->json(['message' => 'Not Found'], 404);
        }

        if (!$request->user()->canPermission('booking:delete')) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        $booking->delete();
        return response()->noContent();
    }
}
