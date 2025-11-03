<?php

namespace App\Http\Controllers;

use App\Models\Photo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PhotoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'photo_type' => 'required|string|in:property,room_type,unit',
            'photo_type_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $photos = Photo::where('photo_type', $request->photo_type)
            ->where('photo_type_id', $request->photo_type_id)
            ->orderBy('sort_order')
            ->get();

        return response()->json($photos);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'photo_type' => 'required|string|in:property,room_type,unit',
            'photo_type_id' => 'required|integer',
            'photos' => 'required|array',
            'photos.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'hosting_company_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $photoPaths = [];
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                $path = $photo->store('photos', 'public');

                $newPhoto = Photo::create([
                    'hosting_company_id' => $request->hosting_company_id,
                    'photo_type' => $request->photo_type,
                    'photo_type_id' => $request->photo_type_id,
                    'photo_path' => $path,
                    'caption' => $request->caption,
                    'is_main' => $request->is_main,
                    'sort_order' => $request->sort_order,
                    'status' => $request->status,
                ]);

                $photoPaths[] = $newPhoto;
            }
        }

        return response()->json($photoPaths, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Photo $photo)
    {
        return response()->json($photo);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Photo $photo)
    {
        $validator = Validator::make($request->all(), [
            'caption' => 'string',
            'is_main' => 'boolean',
            'sort_order' => 'integer',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        if ($request->is_main) {
            Photo::where('photo_type', $photo->photo_type)
                ->where('photo_type_id', $photo->photo_type_id)
                ->update(['is_main' => false]);
        }

        $photo->update($request->all());

        return response()->json($photo);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Photo $photo)
    {
        Storage::disk('public')->delete($photo->photo_path);
        $photo->delete();

        return response()->json(null, 204);
    }
}
