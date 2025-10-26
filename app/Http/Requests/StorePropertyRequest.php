<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePropertyRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Set authorization logic here (e.g., only authenticated hosts can create properties)
        return true; 
    }

    public function rules(): array
    {
        return [
            'user_id' => 'required|exists:users,id',
            'property_category_id' => 'required|exists:property_categories,id',
            'name' => 'required|string|max:255',
            'check_in_time' => 'nullable|date_format:H:i:s',
            'check_out_time' => 'nullable|date_format:H:i:s',
            'min_nights' => 'nullable|integer|min:1',
            'max_nights' => 'nullable|integer|min:1',
            'street_address' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'zip_code' => 'nullable|string|max:20',
            'state_province' => 'nullable|string|max:100',
            'country_code' => 'required|string|max:3',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'registration_terms' => 'nullable|string',
            'deposit_terms' => 'nullable|string',
            'deposit_type' => 'required|in:None,Cash,Bank Transfer',
            'is_non_smoking' => 'boolean',
            'house_rules' => 'nullable|string',
        ];
    }
}