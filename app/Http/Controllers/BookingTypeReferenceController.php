<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookingTypeReferenceResource;
use App\Models\BookingTypeReference;
use Illuminate\Http\Request;

class BookingTypeReferenceController extends Controller
{
    public function index()
    {
        return BookingTypeReferenceResource::collection(BookingTypeReference::all());
    }
}
