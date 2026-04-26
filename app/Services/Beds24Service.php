<?php

namespace App\Services;

use App\Models\SystemSetting;
use App\Models\Property;
use App\Models\RoomType;
use App\Models\Unit;
use App\Models\Channel;
use App\Models\ChannelMapping;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

class Beds24Service
{
    public static $isSyncing = false;
    /**
     * Get a valid Beds24 token, refreshing it if necessary.
     */
    public function getValidToken()
    {
        $inviteKey = SystemSetting::getValue('beds24_invite_key');
        if (!$inviteKey) {
            throw new Exception("Beds24 is not configured. Missing Invite Key.");
        }

        $currentToken = SystemSetting::getValue('beds24_token');
        $expiresAt = SystemSetting::getValue('beds24_token_expires_at');

        // If we have a token and it expires more than 30 minutes from now, return it
        if ($currentToken && $expiresAt) {
            $expirationTime = Carbon::parse($expiresAt);
            if (Carbon::now()->addMinutes(30)->lessThan($expirationTime)) {
                return $currentToken;
            }
        }

        // Token is missing, expired, or expiring soon. Refresh it.
        return $this->refreshToken($inviteKey);
    }

    /**
     * Connect to Beds24 to refresh the temporary token.
     */
    private function refreshToken($inviteKey)
    {
        try {
            $response = Http::withHeaders([
                'accept' => 'application/json',
                'refreshToken' => $inviteKey,
            ])->get('https://beds24.com/api/v2/authentication/token');

            if ($response->failed()) {
                Log::error('Beds24 Token Refresh Failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                throw new Exception("Failed to authenticate with Beds24. Please check your Refresh Token/Invite Key.");
            }

            $data = $response->json();
            $newToken = $data['token'];
            
            // Per API v2 docs, tokens are valid for 24h. We save expiring in 23 hours to be safe.
            $expiresAt = Carbon::now()->addHours(23)->toDateTimeString();

            SystemSetting::setValue('beds24_token', $newToken, 'string');
            SystemSetting::setValue('beds24_token_expires_at', $expiresAt, 'string');

            return $newToken;

        } catch (\Throwable $e) {
            Log::error('Beds24 Refresh Exception: ' . $e->getMessage());
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Fetch all properties from Beds24 using the dynamic rotating token.
     */
    public function getProperties()
    {
        $token = $this->getValidToken();

        $response = Http::withHeaders([
            'accept' => 'application/json',
            'token' => $token,
        ])->get('https://beds24.com/api/v2/properties');

        if ($response->failed()) {
            throw new Exception("Failed to fetch properties from Beds24.");
        }

        $data = $response->json();

        if (isset($data['data'])) {
            $localPropertyNames = \App\Models\Property::pluck('name')->toArray();
            foreach ($data['data'] as &$prop) {
                $prop['is_imported'] = in_array($prop['name'], $localPropertyNames);
            }
        }

        return $data;
    }

    /**
     * Import a completely new property from Beds24 into the HostHome database.
     */
    public function importProperty($beds24PropertyId, $hostingCompanyId, $ownerId)
    {
        $token = $this->getValidToken();

        // 1. Fetch full payload (with roomTypes included)
        $response = Http::withHeaders([
            'accept' => 'application/json',
            'token' => $token,
        ])->get("https://beds24.com/api/v2/properties?id={$beds24PropertyId}&includeLanguages=&includeTexts=all&includePictures=true&includeOffers=true&includePriceRules=true&includeUpsellItems=true&includeAllRooms=true&includeUnitDetails=true");

        if ($response->failed()) {
            Log::error('Beds24 importProperty Fetch Failed', [
                'status' => $response->status(),
                'body' => $response->json(),
                'propertyId' => $beds24PropertyId
            ]);
            throw new Exception("Beds24 API Error: " . ($response->json()['error'] ?? "Failed to fetch Property {$beds24PropertyId}."));
        }

        $beds24Property = $response->json();
        
        // Handle variations in array structure sometimes returned by beds24
        if (isset($beds24Property['data'])) {
            $beds24Property = $beds24Property['data'][0] ?? $beds24Property['data'];
        }

        return DB::transaction(function () use ($beds24Property, $hostingCompanyId, $ownerId, $beds24PropertyId) {
            
            // 2. Ensure Beds24 Channel exists
            $channel = Channel::firstOrCreate(
                ['external_system_code' => 'beds24'],
                ['name' => 'Beds24', 'status' => 1]
            );

            // 3. Create the Local Property
            $property = Property::create([
                'property_owner_id' => $ownerId,
                'hosting_company_id' => $hostingCompanyId,
                'name' => $beds24Property['name'],
                'address_line_1' => $beds24Property['address'] ?? null,
                'city' => $beds24Property['city'] ?? null,
                'country' => $beds24Property['country'] ?? null,
                'zip_code' => $beds24Property['postcode'] ?? null,
                'check_in_time' => $beds24Property['checkInStart'] ?? null,
                'check_out_time' => $beds24Property['checkOutEnd'] ?? null,
                'min_nights' => !empty($beds24Property['roomTypes']) ? $beds24Property['roomTypes'][0]['minStay'] ?? null : null,
                'max_nights' => !empty($beds24Property['roomTypes']) ? $beds24Property['roomTypes'][0]['maxStay'] ?? null : null,
                'listing_status' => 'draft',
                'status' => 1,
            ]);

            // Optional: You would also save the hosting_company_id via a pivot or relation if your architecture requires it.
            // But 'property_owner_id' binds it by proxy to the Hosting Company.

            // 4. Create Room Types and Property Units
            if (!empty($beds24Property['roomTypes'])) {
                foreach ($beds24Property['roomTypes'] as $roomTypeData) {
                    
                    // Add Room Type
                    $roomType = RoomType::create([
                        'property_id' => $property->id,
                        'hosting_company_id' => $hostingCompanyId,
                        'name' => $roomTypeData['name'],
                        'max_adults' => $roomTypeData['maxAdult'] ?? ($roomTypeData['maxPeople'] ?? 2),
                        'max_children' => $roomTypeData['maxChildren'] ?? 0,
                        'size' => $roomTypeData['roomSize'] ?? null,
                        'weekday_price' => $roomTypeData['rackRate'] ?? 0,
                         'weekend_price' => $roomTypeData['rackRate'] ?? 0,
                        'status' => 1,
                    ]);

                    // Add Property Unit (1 unit per roomType for now, or match qty)
                    $qty = $roomTypeData['qty'] ?? 1;
                    
                    for ($i = 0; $i < $qty; $i++) {
                        $unit = Unit::create([
                            'property_id' => $property->id,
                            'room_type_id' => $roomType->id,
                            'owner_user_id' => $ownerId,
                            'unit_identifier' => "{$roomTypeData['name']} - " . ($i + 1),
                            'status' => 'available',
                        ]);

                        // 5. Map Unit to Beds24 via channels_mapping
                        ChannelMapping::updateOrCreate(
                            [
                                'property_unit_id' => $unit->id,
                                'channel_id' => $channel->id,
                            ],
                            [
                                'external_unit_id' => (string)$roomTypeData['id'], // Beds24 roomId
                                'status' => 1
                            ]
                        );
                    }
                }
            }

            // 6. Automatically sync calendar to capture real weekday/weekend pricing overrides
            try {
                $this->syncCalendar($beds24PropertyId, $property->id);
            } catch (\Exception $e) {
                Log::warning("Initial Calendar Sync failed during import for Property ID {$property->id}: " . $e->getMessage());
            }

            return $property;
        });
    }

    /**
     * Map an existing Beds24 Property to an existing HostHome Property.
     */
    public function attachProperty($beds24PropertyId, $localPropertyId)
    {
        $token = $this->getValidToken();

        // 1. Fetch Room Types from Beds24
        $response = Http::withHeaders([
            'accept' => 'application/json',
            'token' => $token,
        ])->get("https://beds24.com/api/v2/properties?id={$beds24PropertyId}&includeLanguages=&includeTexts=all&includePictures=true&includeOffers=true&includePriceRules=true&includeUpsellItems=true&includeAllRooms=true&includeUnitDetails=true");

        if ($response->failed()) {
            Log::error('Beds24 attachProperty Fetch Failed', [
                'status' => $response->status(),
                'body' => $response->json(),
                'propertyId' => $beds24PropertyId
            ]);
            throw new Exception("Beds24 API Error: " . ($response->json()['error'] ?? "Failed to fetch Property {$beds24PropertyId}."));
        }

        $beds24Property = $response->json();
        
        if (isset($beds24Property['data'])) {
            $beds24Property = $beds24Property['data'][0] ?? $beds24Property['data'];
        }

        return DB::transaction(function () use ($beds24Property, $localPropertyId) {
            $channel = Channel::firstOrCreate(
                ['external_system_code' => 'beds24'],
                ['name' => 'Beds24', 'status' => 1]
            );

            $localProperty = Property::with('roomTypes.units')->findOrFail($localPropertyId);

            // We do a simple sequential binding for demonstration. 
            // Often, a more complex visual mapper is used on the frontend, but this will hook them up physically.
            $beds24Rooms = $beds24Property['roomTypes'] ?? [];
            $b_index = 0;

            foreach ($localProperty->roomTypes as $localRoom) {
                foreach ($localRoom->units as $localUnit) {
                    if (isset($beds24Rooms[$b_index])) {
                        ChannelMapping::updateOrCreate(
                            [
                                'property_unit_id' => $localUnit->id,
                                'channel_id' => $channel->id,
                            ],
                            [
                                'external_unit_id' => (string)$beds24Rooms[$b_index]['id'],
                                'status' => 1
                            ]
                        );
                    }
                    $b_index++;
                }
            }

            return $localProperty;
        });
    }

    /**
     * Sync general pricing and availability stats directly into the local property and room models.
     * Hits the Beds24 calendar endpoint, categorizes pricing, and updates tables.
     */
    public function syncCalendar($beds24PropertyId, $localPropertyId)
    {
        $token = $this->getValidToken();

        // 1. Define window (e.g., next 90 days to establish a baseline)
        $startDate = \Carbon\Carbon::now()->format('Y-m-d');
        $endDate = \Carbon\Carbon::now()->addDays(90)->format('Y-m-d');

        $localProperty = Property::with('roomTypes.units')->findOrFail($localPropertyId);

        return DB::transaction(function () use ($beds24PropertyId, $localProperty, $startDate, $endDate, $token) {
            
            // Track min/max stays across all rooms for the property level
            $globalMinNights = [];
            $globalMaxNights = [];

            // We loop through each local room type and find its corresponding external_unit_id (roomId)
            foreach ($localProperty->roomTypes as $roomType) {
                
                $mappedUnit = $roomType->units->first();
                if (!$mappedUnit) continue;

                $channelMapping = ChannelMapping::where('property_unit_id', $mappedUnit->id)
                    ->whereHas('channel', function($q) { $q->where('external_system_code', 'beds24'); })
                    ->first();

                if (!$channelMapping || empty($channelMapping->external_unit_id)) continue;
                
                $beds24RoomId = $channelMapping->external_unit_id;

                // 2. Fetch Calendar payload
                $url = "https://beds24.com/api/v2/inventory/rooms/calendar?startDate={$startDate}&endDate={$endDate}&roomId={$beds24RoomId}&propertyId={$beds24PropertyId}&includeMinStay=true&includeMaxStay=true&includePrices=true";
                
                $response = Http::withHeaders([
                    'accept' => 'application/json',
                    'token' => $token,
                ])->get($url);

                if ($response->failed()) {
                    Log::error("Beds24 Calendar Sync Failed for Room {$beds24RoomId}");
                    continue;
                }

                $calData = $response->json();
                if (empty($calData['data'][0]['calendar'])) continue;

                $calendarArrays = $calData['data'][0]['calendar'];

                // 3. Aggregate Prices
                $weekdays = []; // Sun-Thu
                $weekends = []; // Fri-Sat
                
                foreach ($calendarArrays as $block) {
                    $price = (string)($block['price1'] ?? 0);
                    $minStay = $block['minStay'] ?? null;
                    $maxStay = $block['maxStay'] ?? null;

                    if ($minStay !== null) $globalMinNights[] = $minStay;
                    if ($maxStay !== null) $globalMaxNights[] = $maxStay;

                    if ((float)$price > 0) {
                        try {
                            $fromDate = \Carbon\Carbon::parse($block['from']);
                            $toDate = \Carbon\Carbon::parse($block['to']);
                            
                            $currentDate = $fromDate->copy();

                            // Use strictly less than (lt) because checkout day doesn't incur a new night's cost
                            while ($currentDate->lt($toDate)) {
                                $dayOfWeek = $currentDate->dayOfWeek; // 0=Sun, 1=Mon...6=Sat
                                if (in_array($dayOfWeek, [5, 6])) { // Strictly Friday (5) and Saturday (6)
                                    $weekends[] = $price;
                                } else {
                                    $weekdays[] = $price;
                                }
                                $currentDate->addDay();
                            }
                        } catch (\Exception $e) {
                             // Date parsing failed
                        }
                    }
                }

                // 4. Calculate Mode (Most common number) or simple Average
                $calcModeOrAvg = function($arr) {
                    if (empty($arr)) return 0;
                    $counts = array_count_values($arr);
                    arsort($counts);
                    $mode = key($counts); // Take the most frequently occurring legitimate price
                    return (float)$mode; 
                };

                $roomWeekdayPrice = round($calcModeOrAvg($weekdays), 2);
                $roomWeekendPrice = round($calcModeOrAvg($weekends), 2);

                // Update the DB immediately
                if ($roomWeekdayPrice > 0) {
                   $roomType->update([
                       'weekday_price' => $roomWeekdayPrice,
                       'weekend_price' => $roomWeekendPrice > 0 ? $roomWeekendPrice : $roomWeekdayPrice
                   ]);
                }
            } // End RoomType Loop

            // 5. Update overall Property min/max nights
            if (!empty($globalMinNights)) {
                $localProperty->update([
                    'min_nights' => min($globalMinNights),
                    'max_nights' => max($globalMaxNights)
                ]);
            }

            return true;
        });
    }

    /**
     * Update a room's daily price in hosthome and push the single-day override to Beds24
     */
    public function updateRoomPrice($roomTypeId, $date, $price)
    {
        $roomType = \App\Models\RoomType::findOrFail($roomTypeId);
        $parsedDate = \Carbon\Carbon::parse($date);
        $dayOfWeek = $parsedDate->dayOfWeek;
        $isWeekendUpdate = in_array($dayOfWeek, [5, 6]); // Friday (5), Saturday (6)
        
        // 1. Update Local HostHome Database
        if ($isWeekendUpdate) {
            $roomType->update(['weekend_price' => $price]);
        } else {
            $roomType->update(['weekday_price' => $price]);
        }
        
        // 2. Push Override to Beds24 for the entire month
        $startOfMonth = $parsedDate->copy()->startOfMonth();
        $endOfMonth = $parsedDate->copy()->endOfMonth();
        
        $calendarUpdates = [];
        for ($currentDate = $startOfMonth; $currentDate->lte($endOfMonth); $currentDate->addDay()) {
            $isCurrentWeekend = in_array($currentDate->dayOfWeek, [5, 6]);
            
            // If the user selected a weekend to update, update all weekends in the month.
            // If the user selected a weekday, update all weekdays.
            if ($isCurrentWeekend === $isWeekendUpdate) {
                $calendarUpdates[] = [
                    'from' => $currentDate->format('Y-m-d'),
                    'to' => $currentDate->format('Y-m-d'),
                    'price1' => (float)$price
                ];
            }
        }

        // Find the unit linked to this room type to get the external channel mapping
        $unit = $roomType->units()->first();
        if ($unit) {
            $mapping = \App\Models\ChannelMapping::where('property_unit_id', $unit->id)
                ->whereHas('channel', function($q) {
                    $q->where('external_system_code', 'beds24');
                })->first();
                
            if ($mapping && $mapping->external_unit_id) {
                $roomId = $mapping->external_unit_id;
                $token = $this->getValidToken();
                
                $payload = [
                    [
                        'roomId' => (int)$roomId,
                        'calendar' => $calendarUpdates
                    ]
                ];
                
                $response = \Illuminate\Support\Facades\Http::withHeaders([
                    'Content-Type' => 'application/json',
                    'token' => $token,
                ])->post('https://beds24.com/api/v2/inventory/rooms/calendar', $payload);
                
                if ($response->failed()) {
                    \Illuminate\Support\Facades\Log::error('Beds24 sync override failed', ['response' => $response->json()]);
                    throw new \Exception('Failed to push override to Beds24: ' . ($response->json()['error'] ?? 'Unknown Error'));
                }
            }
        }
    }

    /**
     * Fetch a single booking from Beds24 and save/update it locally.
     */
    public function fetchAndSaveBooking($bookId)
    {
        $token = $this->getValidToken();

        $response = Http::withHeaders([
            'accept' => 'application/json',
            'token' => $token,
        ])->get("https://beds24.com/api/v2/bookings?id={$bookId}");

        if ($response->failed()) {
            Log::error("Beds24 fetchAndSaveBooking Failed for ID {$bookId}", [
                'status' => $response->status(),
                'body' => $response->json()
            ]);
            throw new Exception("Failed to fetch booking {$bookId} from Beds24.");
        }

        $bookingData = $response->json();
        if (isset($bookingData['data'])) {
            $bookingData = $bookingData['data'][0] ?? $bookingData['data'];
        }

        if (empty($bookingData)) {
            throw new Exception("Booking {$bookId} not found in Beds24.");
        }

        self::$isSyncing = true;
        try {
            return DB::transaction(function () use ($bookingData, $bookId) {
                // 1. Find the local Unit using the external roomId
                $externalRoomId = (string)($bookingData['roomId'] ?? '');
                
                $mapping = \App\Models\ChannelMapping::where('external_unit_id', $externalRoomId)
                    ->whereHas('channel', function($q) {
                        $q->where('external_system_code', 'beds24');
                    })->first();

                if (!$mapping) {
                    Log::warning("Beds24 Sync: Could not find local mapping for external roomId {$externalRoomId}");
                    return null;
                }

                $unit = $mapping->propertyUnit;
                if (!$unit) {
                    Log::warning("Beds24 Sync: Mapping exists but unit is missing for external roomId {$externalRoomId}");
                    return null;
                }

                // 2. Identify or Create the Guest
                $guestEmail = $bookingData['email'] ?? null;
                $guestPhone = $bookingData['phone'] ?? null;
                $guestFirstName = $bookingData['firstName'] ?? 'Guest';
                $guestLastName = $bookingData['lastName'] ?? $bookId;
                $guestAddress = $bookingData['address'] ?? null;
                $guestCity = $bookingData['city'] ?? null;

                $guest = \App\Models\Guest::where(function($q) use ($guestEmail, $guestPhone) {
                    if ($guestEmail) $q->where('email', $guestEmail);
                    if ($guestPhone) $q->orWhere('phone_number', $guestPhone);
                })->first();

                if (!$guest) {
                    $guest = \App\Models\Guest::create([
                        'hosting_company_id' => $unit->property->hosting_company_id,
                        'first_name' => $guestFirstName,
                        'last_name' => $guestLastName,
                        'email' => $guestEmail,
                        'phone_number' => $guestPhone,
                        'address' => $guestAddress,
                        'city' => $guestCity,
                        'status' => 1
                    ]);
                }

                // 3. Update or Create the Booking
                $statusMapping = [
                    'confirmed' => 1,
                    'new' => 1,
                    'modified' => 1,
                    'cancelled' => 2,
                ];
                $localStatus = $statusMapping[$bookingData['status'] ?? 'new'] ?? 1;

                $booking = \App\Models\Booking::updateOrCreate(
                    ['external_reservation_id' => (string)$bookId],
                    [
                        'property_id' => $unit->property_id,
                        'room_type_id' => $unit->room_type_id,
                        'property_unit_id' => $unit->id,
                        'guest_id' => $guest->id,
                        'hosting_company_id' => $unit->property->hosting_company_id,
                        'check_in_date' => $bookingData['arrival'],
                        'check_out_date' => $bookingData['departure'],
                        'number_of_guests' => ($bookingData['numAdult'] ?? 1) + ($bookingData['numChild'] ?? 0),
                        'total_amount' => $bookingData['price'] ?? 0,
                        'amount_paid' => $bookingData['deposit'] ?? 0,
                        'status' => $localStatus,
                        'channel_id' => $mapping->channel_id,
                        'channel_source' => $bookingData['apiSource'] ?? ($bookingData['channel'] ?? 'Direct'),
                        'channel_booking_id' => $bookingData['apiReference'] ?? ($bookingData['reference'] ?? null),
                        'confirmation_code' => 'B24-' . $bookId,
                        'booking_type_reference_id' => 1, // Assuming 1 is standard/external
                    ]
                );

                return $booking;
            });
        } finally {
            self::$isSyncing = false;
        }
    }

    /**
     * Bulk import bookings for a property from Beds24.
     */
    public function importBookings($localPropertyId)
    {
        $property = Property::findOrFail($localPropertyId);
        
        // Find if this property has any mapped units to get the Beds24 Property ID
        $mapping = ChannelMapping::whereHas('propertyUnit', function($q) use ($localPropertyId) {
            $q->where('property_id', $localPropertyId);
        })->whereHas('channel', function($q) {
            $q->where('external_system_code', 'beds24');
        })->first();

        if (!$mapping) {
            throw new Exception("Property is not mapped to Beds24.");
        }

        // We need the Beds24 propertyId. It's usually known if we imported the property.
        // Let's assume we can fetch all bookings for the account filtered by property if we had the propId.
        // But the v2 bookings API allows filtering by propertyId.
        // If we don't have it explicitly stored, we might need to fetch it or pass it.
        // For now, let's try to find it from the first mapped roomId's property.
        
        $token = $this->getValidToken();
        
        // We'll fetch the property details from Beds24 to get the actual Beds24 property ID if needed, 
        // but often the mapping is enough if we just want to sync what we have.
        
        // Fetch bookings from last 30 days to future
        $arrivalFrom = Carbon::now()->subDays(30)->format('Y-m-d');
        
        $response = Http::withHeaders([
            'accept' => 'application/json',
            'token' => $token,
        ])->get("https://beds24.com/api/v2/bookings?arrivalFrom={$arrivalFrom}");

        if ($response->failed()) {
            throw new Exception("Failed to fetch bookings list from Beds24.");
        }

        $bookings = $response->json();
        $importedCount = 0;

        if (isset($bookings['data'])) {
            foreach ($bookings['data'] as $b) {
                try {
                    $res = $this->fetchAndSaveBooking($b['id']);
                    if ($res) $importedCount++;
                } catch (\Exception $e) {
                    Log::error("Bulk Import: Failed for booking {$b['id']}: " . $e->getMessage());
                }
            }
        }

        return $importedCount;
    }

    /**
     * Bulk import bookings for all mapped properties from Beds24.
     */
    public function bulkImportAllBookings()
    {
        // Find all property IDs that have at least one unit mapped to Beds24
        $propertyIds = \App\Models\Property::whereHas('roomTypes.units.channelMappings', function($q) {
            $q->whereHas('channel', function($query) {
                $query->where('external_system_code', 'beds24');
            });
        })->pluck('id');

        // print_r($propertyIds);

        $totalImported = 0;
        foreach ($propertyIds as $propertyId) {
            try {
                $totalImported += $this->importBookings($propertyId);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Bulk Import All: Failed for property {$propertyId}: " . $e->getMessage());
            }
        }

        return $totalImported;
    }

    /**
     * Push a local booking to Beds24 to block dates.
     */
    public function pushBookingToBeds24(\App\Models\Booking $booking)
    {
        if (self::$isSyncing) {
            return null;
        }

        self::$isSyncing = true;
        
        // 1. Identify the Beds24 Room ID from channel mapping
        $mapping = \App\Models\ChannelMapping::where('property_unit_id', $booking->property_unit_id)
            ->whereHas('channel', function($q) {
                $q->where('external_system_code', 'beds24');
            })->first();

        if (!$mapping) {
            return null; // Not a Beds24 room
        }

        $token = $this->getValidToken();

        // 2. Prepare payload
        // Documentation suggests an array of booking objects
        $payload = [
            [
                'roomId' => (int)$mapping->external_unit_id,
                'status' => $booking->status == 2 ? 'cancelled' : 'confirmed',
                'arrival' => $booking->check_in_date,
                'departure' => $booking->check_out_date,
                'numAdult' => (int)$booking->number_of_guests,
                'firstName' => $booking->guest->first_name ?? 'Guest',
                'lastName' => $booking->guest->last_name ?? 'HostHome',
                'email' => $booking->guest->email ?? '',
                'phone' => $booking->guest->phone_number ?? '',
                'notes' => $booking->remarks ?? 'Sync from HostHome',
                'apiSource' => 'HostHome',
            ]
        ];

        // If it was already a Beds24 booking, include the ID to update it
        if ($booking->external_reservation_id) {
            $payload[0]['id'] = (int)$booking->external_reservation_id;
        }

        try {
            $response = Http::withHeaders([
                'accept' => 'application/json',
                'token' => $token,
            ])->post('https://beds24.com/api/v2/bookings', $payload);

            if ($response->failed()) {
                Log::error("Beds24 Push Failed", [
                    'status' => $response->status(),
                    'body' => $response->json(),
                    'booking_id' => $booking->id
                ]);
                return false;
            }

            $result = $response->json();
            
            // Beds24 V2 returns an array of results
            $bookingResult = $result[0] ?? null;

            if ($bookingResult && isset($bookingResult['success']) && $bookingResult['success']) {
                // If it's a new booking on Beds24 side, store the ID
                if (isset($bookingResult['new']['id'])) {
                    // Update WITHOUT triggering observers again (using quiet if available or manual update)
                    DB::table('bookings')
                        ->where('id', $booking->id)
                        ->update(['external_reservation_id' => (string)$bookingResult['new']['id']]);
                }
                return true;
            }

            Log::warning("Beds24 Push Warning: Success was false", ['result' => $result]);
            return false;

        } catch (\Exception $e) {
            Log::error("Beds24 Push Exception: " . $e->getMessage());
            return false;
        } finally {
            self::$isSyncing = false;
        }
    }

    /**
     * Mark a booking as cancelled on Beds24 (instead of deleting it).
     */
    public function cancelBookingOnBeds24(\App\Models\Booking $booking)
    {
        if (!$booking->external_reservation_id) {
            return null; // Not sync'd with Beds24
        }

        if (self::$isSyncing) {
            return null;
        }

        self::$isSyncing = true;
        $token = $this->getValidToken();

        // Prepare the cancellation payload
        $payload = [
            [
                'id' => (int)$booking->external_reservation_id,
                'status' => 'cancelled',
                'notes' => ($booking->remarks ? $booking->remarks . ' | ' : '') . 'Cancelled from HostHome'
            ]
        ];

        try {
            $response = Http::withHeaders([
                'accept' => 'application/json',
                'token' => $token,
            ])->post('https://beds24.com/api/v2/bookings', $payload);

            if ($response->failed()) {
                Log::error("Beds24 Cancellation Failed", [
                    'status' => $response->status(),
                    'body' => $response->json(),
                    'external_id' => $booking->external_reservation_id
                ]);
                return false;
            }

            $result = $response->json();
            $bookingResult = $result[0] ?? null;

            if ($bookingResult && isset($bookingResult['success']) && $bookingResult['success']) {
                Log::info("Beds24 Cancellation Success", ['external_id' => $booking->external_reservation_id]);
                return true;
            }

            Log::warning("Beds24 Cancellation Warning: Success was false", ['result' => $result]);
            return false;

        } catch (\Exception $e) {
            Log::error("Beds24 Cancellation Exception: " . $e->getMessage());
            return false;
        } finally {
            self::$isSyncing = false;
        }
    }

    /**
     * Send an outbound message to a guest via Beds24 channels (e.g. Airbnb, Booking.com).
     */
    public function sendMessage(\App\Models\Message $message)
    {
        if (!$message->booking || !$message->booking->external_reservation_id) {
            Log::warning("Beds24 SendMessage: Cannot send message, booking is not linked to Beds24.");
            return false;
        }

        $token = $this->getValidToken();

        $payload = [
            [
                'bookId' => (int)$message->booking->external_reservation_id,
                'message' => $message->content
            ]
        ];

        try {
            $response = Http::withHeaders([
                'accept' => 'application/json',
                'token' => $token,
            ])->post('https://beds24.com/api/v2/messages', $payload);

            if ($response->failed()) {
                Log::error("Beds24 SendMessage Failed", [
                    'status' => $response->status(),
                    'body' => $response->json(),
                ]);
                return false;
            }

            $result = $response->json();
            $msgResult = $result[0] ?? null;

            if ($msgResult && isset($msgResult['success']) && $msgResult['success']) {
                $message->update([
                    'status' => 'delivered',
                    'external_message_id' => $msgResult['new']['id'] ?? null
                ]);
                return true;
            }

            return false;

        } catch (\Exception $e) {
            Log::error("Beds24 SendMessage Exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Process an inbound message from Beds24Webhook 
     * Payload expected to contain bookId, messageId, and text.
     */
    public function handleInboundMessage(array $payload)
    {
        $bookId = $payload['bookId'] ?? null;
        $messageText = $payload['message'] ?? null;
        $messageId = $payload['id'] ?? null;

        if (!$bookId || !$messageText) {
            return false;
        }

        $booking = \App\Models\Booking::where('external_reservation_id', (string)$bookId)->first();
        if (!$booking) {
            Log::warning("Beds24 InboundMessage: Booking ID {$bookId} not found locally.");
            return false;
        }

        // Check if message already exists
        if ($messageId && \App\Models\Message::where('external_message_id', (string)$messageId)->exists()) {
            return true; // Already processed
        }

        \App\Models\Message::create([
            'booking_id' => $booking->id,
            'guest_id' => $booking->guest_id,
            'direction' => 'inbound',
            'channel' => 'beds24',
            'external_message_id' => (string)$messageId,
            'content' => $messageText,
            'status' => 'delivered',
            'sent_at' => now(),
        ]);

        return true;
    }
}
