<?php
require 'vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$prop = \App\Models\Property::with('roomTypes')->latest()->first();
echo "Property ID: " . $prop->id . "\n";
foreach ($prop->roomTypes as $rt) {
    echo "Before: wkd=" . $rt->weekday_price . " wnd=" . $rt->weekend_price . "\n";
}

$channelMapping = \App\Models\ChannelMapping::whereHas('channel', function($q) {
    $q->where('external_system_code', 'beds24');
})->whereIn('property_unit_id', $prop->roomTypes->flatMap->units->pluck('id'))->first();

if ($channelMapping) {
    echo "Found mapping inside property. Beds24 roomId: " . $channelMapping->external_unit_id . "\n";
} else {
    echo "NO MAPPING FOUND!\n";
}

app(\App\Services\Beds24Service::class)->syncCalendar(315297, $prop->id);

$prop->refresh();
$prop->load('roomTypes');
foreach ($prop->roomTypes as $rt) {
    echo "After: wkd=" . $rt->weekday_price . " wnd=" . $rt->weekend_price . "\n";
}
