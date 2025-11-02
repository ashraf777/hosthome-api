<?php

namespace App\Http\Controllers;

use App\Models\RoomType;
use App\Models\RoomTypePhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

// CORRECT: Extends the base Controller
class RoomTypePhotoController extends Controller
{
    public function index(Request $request, RoomType $roomType)
    {
        // CORRECT: Tenancy Check
        if ($roomType->property->hosting_company_id !== $request->user()->hosting_company_id) {
            return response()->json(['message' => 'Not Found'], 404);
        }

        // CORRECT: Using the application's canPermission() method
        // if (!$request->user()->canPermission('room-type-photo:view')) {
        //     return response()->json(['message' => 'This action is unauthorized.'], 403);
        // }

        return response()->json($roomType->photos);
    }

    public function store(Request $request, RoomType $roomType)
    {
        if ($roomType->property->hosting_company_id !== $request->user()->hosting_company_id) {
            return response()->json(['message' => 'Not Found'], 404);
        }

        if (!$request->user()->canPermission('room-type-photo:create')) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        $request->validate([
            'photo' => 'required|image|max:2048',
            'caption' => 'nullable|string',
            'is_main' => 'nullable|boolean',
        ]);

        $path = $request->file('photo')->store('room-type-photos', 'public');

        if ($request->input('is_main', false)) {
            $roomType->photos()->update(['is_main' => false]);
        }

        $photo = $roomType->photos()->create([
            'photo_path' => $path,
            'caption' => $request->caption,
            'is_main' => $request->input('is_main', false),
        ]);

        return response()->json($photo, 201);
    }

    public function update(Request $request, RoomType $roomType, RoomTypePhoto $photo)
    {
        if ($roomType->property->hosting_company_id !== $request->user()->hosting_company_id) {
            return response()->json(['message' => 'Not Found'], 404);
        }

        if (!$request->user()->canPermission('room-type-photo:update')) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        $request->validate([
            'caption' => 'sometimes|string',
            'is_main' => 'sometimes|boolean',
        ]);

        if ($request->input('is_main', false)) {
            $roomType->photos()->where('id', '!=', $photo->id)->update(['is_main' => false]);
        }

        $photo->update($request->only(['caption', 'is_main']));

        return response()->json($photo);
    }

    public function destroy(Request $request, RoomType $roomType, RoomTypePhoto $photo)
    {
        if ($roomType->property->hosting_company_id !== $request->user()->hosting_company_id) {
            return response()->json(['message' => 'Not Found'], 404);
        }

        if (!$request->user()->canPermission('room-type-photo:delete')) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        Storage::disk('public')->delete($photo->photo_path);
        $photo->delete();

        return response()->noContent();
    }
}
