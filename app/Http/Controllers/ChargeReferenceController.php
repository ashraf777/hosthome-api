<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\ChargeReferenceResource;
use App\Models\ChargeReference;
use Illuminate\Http\Request;

class ChargeReferenceController extends Controller
{
    public function index()
    {
        return ChargeReferenceResource::collection(ChargeReference::all());
    }
}