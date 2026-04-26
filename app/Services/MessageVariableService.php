<?php

namespace App\Services;

use App\Models\Booking;

class MessageVariableService
{
    /**
     * Parses a string template and replaces placeholders with actual booking data.
     *
     * Supported Placeholders:
     * {guest_first_name}, {guest_last_name}, {guest_phone}
     * {property_name}, {unit_name}, {hosting_company}
     * {check_in_date}, {check_out_date}, {num_adults}, {num_children}
     * {booking_status}, {total_amount}, {amount_paid}
     */
    public function parse(string $templateText, Booking $booking): string
    {
        // Ensure relationships are loaded
        $booking->loadMissing(['guest', 'property', 'propertyUnit', 'hostingCompany']);

        $guest = $booking->guest;
        $property = $booking->property;
        $unit = $booking->propertyUnit;
        $company = $booking->hostingCompany;

        // Map placeholders to values
        $replacements = [
            '{guest_first_name}' => $guest ? $guest->first_name : 'Guest',
            '{guest_last_name}' => $guest ? $guest->last_name : '',
            '{guest_phone}' => $guest ? $guest->phone_number : '',
            
            '{property_name}' => $property ? $property->name : 'Our Property',
            '{unit_name}' => $unit ? $unit->name : 'Your Room',
            '{hosting_company}' => $company ? $company->name : 'The Host',
            
            '{check_in_date}' => $booking->check_in_date,
            '{check_out_date}' => $booking->check_out_date,
            '{num_adults}' => $this->cloneValue($booking->num_adults),
            '{num_children}' => $this->cloneValue($booking->num_children),
            
            '{booking_status}' => $booking->booking_status,
            '{total_amount}' => 'RM ' . number_format($booking->total_amount, 2),
            '{amount_paid}' => 'RM ' . number_format($booking->amount_paid, 2),
        ];

        $parsedText = str_replace(
            array_keys($replacements),
            array_values($replacements),
            $templateText
        );

        return $parsedText;
    }

    private function cloneValue($val) {
        return $val ?? '0';
    }
}

