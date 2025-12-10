<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\BedTypeReferenceResource; // Import the resource
use App\Models\BedTypeReference; // Import the model
use Illuminate\Http\Request;

class BedTypeReferenceController extends Controller
{
    /**
     * Display a listing of the resource (all bed type references).
     */
    public function index()
    {
        // Retrieve all records from the table
        $bedTypes = BedTypeReference::all();

        // Wrap the collection of models using the BedTypeReferenceResource
        return BedTypeReferenceResource::collection($bedTypes);
    }
}