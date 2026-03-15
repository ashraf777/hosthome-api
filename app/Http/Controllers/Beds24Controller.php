<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SystemSetting;
use App\Services\Beds24Service;
use Exception;

class Beds24Controller extends Controller
{
    protected $beds24Service;

    public function __construct(Beds24Service $beds24Service)
    {
        $this->beds24Service = $beds24Service;
    }

    /**
     * Test and save the Beds24 permanent Invite Key.
     */
    public function storeConfig(Request $request)
    {
        $request->validate([
            'invite_key' => 'required|string'
        ]);

        $inviteKey = $request->invite_key;

        // Save the key permanently to settings
        SystemSetting::setValue('beds24_invite_key', $inviteKey);

        try {
            // Force a rotation/test of the new token to ensure it actually works
            // This will throw if the invite key is invalid.
            SystemSetting::setValue('beds24_token', null); 
            $this->beds24Service->getValidToken();

            return response()->json([
                'success' => true,
                'message' => 'Beds24 Connected Successfully!'
            ]);

        } catch (Exception $e) {
            // Rollback the bad key so it doesn't break things later
            SystemSetting::setValue('beds24_invite_key', null);
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get the connection status of the Beds24 integration.
     */
    public function checkStatus()
    {
        $inviteKey = SystemSetting::getValue('beds24_invite_key');
        return response()->json([
            'is_connected' => !empty($inviteKey)
        ]);
    }

    /**
     * Fetch all properties from Beds24 utilizing the robust rotation service.
     */
    public function getProperties()
    {
        try {
            $properties = $this->beds24Service->getProperties();
            return response()->json($properties);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Import a property from Beds24 as a new HostHome listing.
     */
    public function importProperty(Request $request)
    {
        $request->validate([
            'beds24_property_id' => 'required|integer',
            'hosting_company_id' => 'required|integer|exists:hosting_companies,id',
            'property_owner_id' => 'required|integer|exists:property_owners,id',
        ]);

        try {
            $property = $this->beds24Service->importProperty(
                $request->beds24_property_id,
                $request->hosting_company_id,
                $request->property_owner_id
            );

            return response()->json([
                'success' => true,
                'message' => 'Property imported successfully.',
                'data' => $property
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Attach a Beds24 property to an existing HostHome property.
     */
    public function attachProperty(Request $request)
    {
        $request->validate([
            'beds24_property_id' => 'required|integer',
            'local_property_id' => 'required|integer|exists:properties,id',
        ]);

        try {
            $property = $this->beds24Service->attachProperty(
                $request->beds24_property_id,
                $request->local_property_id
            );

            return response()->json([
                'success' => true,
                'message' => 'Property attached successfully.',
                'data' => $property
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Trigger a calendar sync for a specific property.
     */
    public function syncCalendar(Request $request)
    {
        $request->validate([
            'beds24_property_id' => 'required|integer',
            'local_property_id' => 'required|integer|exists:properties,id',
        ]);

        try {
            $this->beds24Service->syncCalendar(
                $request->beds24_property_id,
                $request->local_property_id
            );

            return response()->json([
                'success' => true,
                'message' => 'Calendar synced successfully. Prices and Availability updated.'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Push a price override to both local DB and Beds24
     */
    public function updatePrice(Request $request)
    {
        $request->validate([
            'room_type_id' => 'required|integer|exists:room_types,id',
            'date' => 'required|date',
            'price' => 'required|numeric'
        ]);

        try {
            $this->beds24Service->updateRoomPrice(
                $request->room_type_id,
                $request->date,
                $request->price
            );

            return response()->json([
                'success' => true,
                'message' => 'Price updated successfully across HostHome and Beds24.'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle inbound booking webhooks from Beds24.
     */
    public function handleBookingWebhook(Request $request)
    {
        // Beds24 can send various formats.
        $bookId = $request->input('bookId');

        // Robust parsing: check body if input is missing
        if (!$bookId) {
            $data = json_decode($request->getContent(), true);
            $bookId = $data['bookId'] ?? null;
        }

        // Even more robust: check if the first key is JSON (common in some Beds24 setups)
        if (!$bookId) {
            $all = $request->all();
            if (!empty($all)) {
                $firstKey = array_key_first($all);
                $data = json_decode($firstKey, true);
                if (isset($data['bookId'])) {
                    $bookId = $data['bookId'];
                }
            }
        }

        if (!$bookId) {
            \Illuminate\Support\Facades\Log::warning('Beds24 Webhook: Received payload without bookId', ['payload' => $request->all()]);
            return response()->json(['success' => false, 'message' => 'Missing bookId'], 400);
        }

        try {
            $booking = $this->beds24Service->fetchAndSaveBooking($bookId);
            
            return response()->json([
                'success' => true,
                'message' => 'Booking synchronized successfully.',
                'booking_id' => $booking ? $booking->id : null
            ]);
        } catch (Exception $e) {
            \Illuminate\Support\Facades\Log::error('Beds24 Webhook Sync Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Batch import bookings for a specific local property.
     */
    public function bulkImportBookings(Request $request)
    {
        $request->validate([
            'local_property_id' => 'required|integer|exists:properties,id'
        ]);

        try {
            $count = $this->beds24Service->importBookings($request->local_property_id);
            
            return response()->json([
                'success' => true,
                'message' => "Successfully imported {$count} bookings from Beds24."
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Batch import bookings for all mapped properties.
     */
    public function bulkImportAllBookings()
    {
        try {
            $count = $this->beds24Service->bulkImportAllBookings();
            
            return response()->json([
                'success' => true,
                'message' => "Successfully imported {$count} bookings from Beds24 for all mapped properties."
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
