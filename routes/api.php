<?php

use Illuminate\Support\Facades\Artisan;
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
use App\Http\Controllers\BookingTypeReferenceController;
use App\Http\Controllers\ChargeReferenceController;

use App\Http\Controllers\PricingRuleController;
use App\Http\Controllers\AvailabilityController;

use App\Http\Controllers\RoomTypeController;
use App\Http\Controllers\BedTypeReferenceController;

use App\Http\Controllers\PhotoController;
use App\Http\Controllers\UnitController;

use App\Http\Controllers\AmenityController;
use App\Http\Controllers\AmenityReferenceController;
use App\Http\Controllers\PropertyAmenityController;
use App\Http\Controllers\RoomTypeAmenityController;

use App\Http\Controllers\CostTypeController;
use App\Http\Controllers\SeoMetadataController;

use App\Http\Controllers\MultiCalendarController;

use App\Http\Controllers\TaskController;
use App\Http\Controllers\ChecklistController;
use App\Http\Controllers\CleaningTeamController;
use App\Http\Controllers\PresetTaskController;
use App\Http\Controllers\Beds24Controller;

use App\Http\Controllers\MessageController;
use App\Http\Controllers\MessageTemplateController;
use App\Http\Controllers\AdminNotificationController;

use App\Http\Controllers\Guest\GuestPropertyController;
use App\Http\Controllers\Guest\GuestBookingController;
use App\Http\Controllers\Guest\GuestLookupController;
use App\Http\Controllers\GuestPortalController;

// --- PUBLIC AUTH ROUTES (NO MIDDLEWARE) ---
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

Route::get('users-debug', function () {
    return \App\Models\HostingCompany::where('slug', 'sweet-host')->first();
});

// --- WEBHOOKS (Public) ---
Route::post('webhooks/beds24/booking', [Beds24Controller::class, 'handleBookingWebhook']);
Route::post('webhooks/beds24/message', [Beds24Controller::class, 'handleMessageWebhook']);

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

    // --- GLOBAL LOOKUP RESOURCES ---
    Route::apiResource('property-references', PropertyReferenceController::class)->except(['show']);
    Route::apiResource('amenities-references', AmenityReferenceController::class);
    Route::get('booking-type-references', [BookingTypeReferenceController::class, 'index']);
    Route::get('charge-references', [ChargeReferenceController::class, 'index']);
    Route::get('bed-type-references', [BedTypeReferenceController::class, 'index']);

    // --- HOSTING COMPANY-LEVEL RESOURCES (Tenant-scoped) ---
    Route::apiResource('property-owners', PropertyOwnerController::class);
    Route::apiResource('properties', PropertyController::class);
    Route::post('properties/{property}/room-types/{room_type}', [RoomTypeController::class, 'assignToProperty']);
    Route::delete('properties/{property}/room-types/{room_type}', [RoomTypeController::class, 'removeFromProperty']);
    Route::apiResource('channels', ChannelController::class);
    Route::apiResource('bookings', BookingController::class);

    Route::get('multi-calendar', [MultiCalendarController::class, 'index']);

    Route::apiResource('pricing-rules', PricingRuleController::class);
    Route::get('availability', [AvailabilityController::class, 'getAvailability']);

    Route::get('properties/{property}/room-types', [RoomTypeController::class, 'indexByProperty']);
    Route::apiResource('room-types', RoomTypeController::class);
    Route::apiResource('units', UnitController::class);

    Route::apiResource('amenities', AmenityController::class);
    Route::post('properties/{property}/amenities', [PropertyAmenityController::class, 'store']);
    Route::post('room-types/{room_type}/amenities', [RoomTypeAmenityController::class, 'store']);

    Route::apiResource('cost-types', CostTypeController::class)->only(['index', 'show']);
    Route::get('seo-metadata', [SeoMetadataController::class, 'show']);
    Route::post('seo-metadata', [SeoMetadataController::class, 'store']);
    Route::put('seo-metadata/{seoMetadata}', [SeoMetadataController::class, 'update']);

    // Photo routes
    Route::get('photos', [PhotoController::class, 'index']);
    Route::post('photos', [PhotoController::class, 'store']);
    Route::get('photos/{photo}', [PhotoController::class, 'show']);
    Route::post('photos/{photo}', [PhotoController::class, 'update']);
    Route::delete('photos/{photo}', [PhotoController::class, 'destroy']);

    // --- TASK MANAGER ---
    Route::apiResource('tasks', TaskController::class);
    Route::get('tasks/{task}/logs', [TaskController::class, 'getLogs']);
    Route::apiResource('checklists', ChecklistController::class);
    Route::post('checklists/{checklist}/items', [ChecklistController::class, 'storeItem']);
    Route::put('checklists/{checklist}/items/{item}', [ChecklistController::class, 'updateItem']);
    Route::delete('checklists/{checklist}/items/{item}', [ChecklistController::class, 'destroyItem']);
    Route::apiResource('cleaning-teams', CleaningTeamController::class);
    Route::post('cleaning-teams/{cleaning_team}/sync-members', [CleaningTeamController::class, 'syncMembers']);
    Route::apiResource('preset-tasks', PresetTaskController::class);

    // --- BEDS24 CONFIGURATION ---
    Route::post('beds24/config', [Beds24Controller::class, 'storeConfig']);
    Route::get('beds24/status', [Beds24Controller::class, 'checkStatus']);
    Route::get('beds24/properties', [Beds24Controller::class, 'getProperties']);
    Route::post('beds24/properties/import', [Beds24Controller::class, 'importProperty']);
    Route::post('beds24/properties/attach', [Beds24Controller::class, 'attachProperty']);
    Route::post('beds24/calendar/sync', [Beds24Controller::class, 'syncCalendar']);
    Route::post('beds24/calendar/price', [Beds24Controller::class, 'updatePrice']);
    Route::post('beds24/bookings/import', [Beds24Controller::class, 'bulkImportBookings']);

    // --- GUEST MESSAGING & INBOX ---
    Route::post('message-templates/run-automations', [MessageTemplateController::class, 'runAutomations']);
    Route::apiResource('message-templates', MessageTemplateController::class);
    Route::get('messages', [MessageController::class, 'index']); // Get Inbox Bookings
    Route::get('messages/{booking}/thread', [MessageController::class, 'thread']); // Get full conversation
    Route::post('messages/{booking}/send', [MessageController::class, 'store']); // Send a manual reply

    Route::post('beds24/bookings/import-all', [Beds24Controller::class, 'bulkImportAllBookings']);

    // --- ADMIN NOTIFICATIONS ---
    Route::put('admin/notifications/fcm-token', [UserController::class, 'updateFcmToken']);
    Route::get('admin/notifications', [AdminNotificationController::class, 'index']);
    Route::get('admin/notifications/unread-count', [AdminNotificationController::class, 'unreadCount']);
    Route::put('admin/notifications/read-all', [AdminNotificationController::class, 'markAllRead']);
    Route::put('admin/notifications/{id}/read', [AdminNotificationController::class, 'markRead']);

});

// --- PUBLIC GUEST PORTAL ---
Route::get('guest-portal/{token}', [GuestPortalController::class, 'summary']);
Route::get('guest-portal/{token}/messages', [GuestPortalController::class, 'messages']);
Route::post('guest-portal/{token}/messages', [GuestPortalController::class, 'sendMessage']);

// --- GUEST BOOKING ENGINE API (Public) ---
Route::prefix('guest/{company_slug}')->middleware('tenant.slug')->group(function () {

    // Properties & Search
    Route::get('properties', [GuestPropertyController::class, 'index']);
    Route::get('properties/{id}', [GuestPropertyController::class, 'show']);

    // Lookups
    Route::get('amenities', [GuestLookupController::class, 'amenities']);
    Route::get('countries', [GuestLookupController::class, 'countries']);

    // Booking Flow
    Route::get('availability/check', [GuestBookingController::class, 'checkAvailability']);
    Route::post('bookings/quote', [GuestBookingController::class, 'quote']);
    Route::post('bookings', [GuestBookingController::class, 'store']);
});

Route::get('/clear-all-cache', function () {
    Artisan::call('optimize:clear');
    return "All cache cleared!";
});

// Health Check Endpoint
Route::get('/ping', function () {
    return response()->json([
        'status' => 'ok',
        'message' => 'Hosthome API',
        'api_version' => '1.0.12',
        'build_version' => '1'
    ]);
});

// --- CLEANER APP VERSION CHECK (Public) ---
// Flutter app calls this on launch to check if an update is available.
Route::get('/cleaner/app-version', function () {
    return response()->json([
        'latest_version' => '1.0.0',  // Update this when releasing a new build
        'latest_build' => 1,
        'minimum_version' => '1.0.0',  // Below this, user MUST update
        'force_update' => false,
        'update_url_android' => 'https://play.google.com/store/apps/details?id=com.hosthome.cleaner',
        'update_url_ios' => 'https://apps.apple.com/app/hosthome-cleaner/id0000000000',
        'release_notes' => 'Initial release of the HostHome Cleaner App.',
    ]);
});

// --- CLEANER APP AUTH (Public — no middleware) ---
use App\Http\Controllers\Cleaner\CleanerAuthController;
use App\Http\Controllers\Cleaner\CleanerProfileController;
use App\Http\Controllers\Cleaner\CleanerTaskController;
use App\Http\Controllers\Cleaner\CleanerNotificationController;

Route::prefix('cleaner/auth')->group(function () {
    Route::post('request-pin', [CleanerAuthController::class, 'requestPin']);
    Route::post('verify-pin', [CleanerAuthController::class, 'verifyPin']);
});

// --- CLEANER APP PROTECTED ROUTES ---
Route::prefix('cleaner')->middleware('cleaner.token.check')->group(function () {

    // Auth
    Route::post('auth/logout', [CleanerAuthController::class, 'logout']);

    // Profile
    Route::get('profile', [CleanerProfileController::class, 'show']);
    Route::put('profile', [CleanerProfileController::class, 'update']);
    Route::put('profile/availability', [CleanerProfileController::class, 'updateAvailability']);
    Route::put('profile/fcm-token', [CleanerProfileController::class, 'updateFcmToken']);

    // Tasks
    Route::get('tasks/history', [CleanerTaskController::class, 'history']);
    Route::get('tasks', [CleanerTaskController::class, 'index']);
    Route::get('tasks/{task}', [CleanerTaskController::class, 'show']);
    Route::put('tasks/{task}/status', [CleanerTaskController::class, 'updateStatus']);
    Route::post('tasks/{task}/media', [CleanerTaskController::class, 'uploadMedia']);
    Route::get('tasks/{task}/media', [CleanerTaskController::class, 'listMedia']);

    // Notifications
    Route::get('notifications/unread-count', [CleanerNotificationController::class, 'unreadCount']);
    Route::get('notifications', [CleanerNotificationController::class, 'index']);
    Route::put('notifications/read-all', [CleanerNotificationController::class, 'markAllRead']);
    Route::put('notifications/{id}/read', [CleanerNotificationController::class, 'markRead']);
});

// --- ADMIN: Pending Cleaner PIN Requests (Protected by main admin middleware) ---
Route::middleware('api.token.check')->group(function () {
    Route::get('cleaner/auth/pending-pins', [CleanerAuthController::class, 'pendingPins']);
});

