<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PermissionController;

use App\Http\Controllers\CountryController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\HostingCompanyController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\PropertyOwnerController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\PropertyReferenceController;
use App\Http\Controllers\ChannelController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\PricingRuleController;
use App\Http\Controllers\AvailabilityController;

use App\Http\Controllers\RoomTypeController;
use App\Http\Controllers\RoomTypePhotoController;
use App\Http\Controllers\UnitController;

use App\Http\Controllers\AmenityController;
use App\Http\Controllers\AmenityReferenceController;
use App\Http\Controllers\PropertyAmenityController;
use App\Http\Controllers\RoomTypeAmenityController;

use App\Http\Controllers\CostTypeController;
use App\Http\Controllers\SeoMetadataController;

// --- PUBLIC AUTH ROUTES (NO MIDDLEWARE) ---
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

// --- PROTECTED ROUTES ---
// All routes within this group require a valid API token.
Route::middleware('api.token.check')->group(function () {

    // --- USER SELF-MANAGEMENT ---
    Route::get('user', [UserController::class, 'show']);
    Route::put('user', [UserController::class, 'update']);
    Route::post('logout', [AuthController::class, 'logout']);

    // --- RBAC MANAGEMENT ENDPOINTS (Requires Authorization/Permission Checks) ---
    Route::get('permissions', [PermissionController::class, 'index']);
    Route::apiResource('roles', RoleController::class)->except(['destroy']);
    Route::post('roles/{role}/sync-permissions', [RoleController::class, 'syncPermissions']);
    Route::get('users', [UserController::class, 'index']);
    Route::put('users/{user}/role', [UserController::class, 'updateRole']);

    // --- PLATFORM-LEVEL RESOURCES (Typically for Super Admin) ---
    Route::get('countries', [CountryController::class, 'index']);
    Route::apiResource('plans', PlanController::class)->except(['show']);
    Route::apiResource('hosting-companies', HostingCompanyController::class);
    Route::get('hosting-companies/{hostingCompany}/subscriptions', [SubscriptionController::class, 'index']);
    Route::post('hosting-companies/{hostingCompany}/subscriptions', [SubscriptionController::class, 'store']);

    // --- HOSTING COMPANY-LEVEL RESOURCES (Tenant-scoped) ---
    Route::apiResource('property-owners', PropertyOwnerController::class);
    Route::apiResource('properties', PropertyController::class);
    Route::post('properties/{property}/room-types/{room_type}', [RoomTypeController::class, 'assignToProperty']);
    Route::delete('properties/{property}/room-types/{room_type}', [RoomTypeController::class, 'removeFromProperty']);
    Route::apiResource('property-references', PropertyReferenceController::class)->except(['show']);
    Route::apiResource('channels', ChannelController::class);
    Route::apiResource('bookings', BookingController::class);
    Route::apiResource('pricing-rules', PricingRuleController::class);
    Route::get('availability', [AvailabilityController::class, 'getAvailability']);

    Route::get('properties/{property}/room-types', [RoomTypeController::class, 'indexByProperty']);
    Route::apiResource('room-types', RoomTypeController::class);
    Route::apiResource('room-types.photos', RoomTypePhotoController::class)->except(['update']);
    Route::post('room-types/{room_type}/photos/{photo}', [RoomTypePhotoController::class, 'update'])->name('room-types.photos.update');
    Route::apiResource('units', UnitController::class);
    
    Route::apiResource('amenities', AmenityController::class);
    Route::apiResource('amenities-references', AmenityReferenceController::class);
    Route::post('properties/{property}/amenities', [PropertyAmenityController::class, 'store']);
    Route::post('room-types/{room_type}/amenities', [RoomTypeAmenityController::class, 'store']);

    Route::apiResource('cost-types', CostTypeController::class)->only(['index', 'show']);
    Route::get('seo-metadata', [SeoMetadataController::class, 'show']);
    Route::post('seo-metadata', [SeoMetadataController::class, 'store']);
    Route::put('seo-metadata/{seoMetadata}', [SeoMetadataController::class, 'update']);
});

// Health Check Endpoint
Route::get('/ping', function () {
    return response()->json([
        'status' => 'ok',
        'message' => 'Hosthome API',
        'api_version' => '1.0.0',
        'build_version' => '1'
    ]);
});
