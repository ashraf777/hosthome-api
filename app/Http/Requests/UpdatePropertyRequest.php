<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePropertyRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Set authorization to ensure the user owns or can manage this property
        return true; 
    }

    public function rules(): array
    {
        return [
            // Use 'sometimes' for optional updates
            'user_id' => 'sometimes|exists:users,id',
            'property_category_id' => 'sometimes|exists:property_categories,id',
            'name' => 'sometimes|string|max:255',
            'check_in_time' => 'sometimes|nullable|date_format:H:i:s',
            'check_out_time' => 'sometimes|nullable|date_format:H:i:s',
            'min_nights' => 'sometimes|nullable|integer|min:1',
            'max_nights' => 'sometimes|nullable|integer|min:1',
            'street_address' => 'sometimes|required|string|max:255',
            'city' => 'sometimes|required|string|max:100',
            'zip_code' => 'sometimes|nullable|string|max:20',
            'state_province' => 'sometimes|nullable|string|max:100',
            'country_code' => 'sometimes|required|string|max:3',
            'latitude' => 'sometimes|nullable|numeric|between:-90,90',
            'longitude' => 'sometimes|nullable|numeric|between:-180,180',
            'registration_terms' => 'sometimes|nullable|string',
            'deposit_terms' => 'sometimes|nullable|string',
            'deposit_type' => 'sometimes|required|in:None,Cash,Bank Transfer',
            'is_non_smoking' => 'sometimes|boolean',
            'house_rules' => 'sometimes|nullable|string',
        ];
    }
}