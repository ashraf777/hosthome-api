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

use App\Http\Controllers\RoomTypeController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\AmenityController;
use App\Http\Controllers\PropertyCategoryController;
use App\Http\Controllers\AmenityCategoryController;
use App\Http\Controllers\CostTypeController;

// --- PUBLIC AUTH ROUTES (NO MIDDLEWARE) ---
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
// --- PROTECTED ROUTES ---
// 'api.token.check' handles all authentication, status, and tenant scoping
Route::middleware(['api.token.check'])->group(function () {

    // --- USER SELF-MANAGEMENT ---
    Route::get('user', [UserController::class, 'show']);
    Route::put('user', [UserController::class, 'update']);
    Route::post('logout', [AuthController::class, 'logout']);

    // --- RBAC MANAGEMENT ENDPOINTS (Requires Authorization/Permission Checks) ---
    // 1. Permissions (Read-Only Lookup)
    // The 'can' middleware will check if the user's role has the 'role:manage' permission.
    Route::get('permissions', [PermissionController::class, 'index']);
    // 2. Roles (CRUD + Sync)
    Route::prefix('roles')->group(function () {
        Route::get('/', [RoleController::class, 'index']);
        Route::post('/', [RoleController::class, 'store']);
        Route::put('/{role}', [RoleController::class, 'update']);
        Route::post('/{role}/sync-permissions', [RoleController::class, 'syncPermissions']);
    });
    // 3. User Management (Staff/Admin)
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::put('/{user}/role', [UserController::class, 'updateRole']);
    });

    // 4. Country (Read-Only Lookup)
    Route::get('countries', [CountryController::class, 'index']);
    // 5. Plans (CRUD for creating/managing subscription tiers)
    Route::resource('plans', PlanController::class)->except(['show']);
    // 6. HostingCompany (Management of client accounts/tenants)
    Route::resource('hosting-companies', HostingCompanyController::class);
    // 7. Subscriptions (Billing records)
    // Assuming subscriptions are handled by a separate process (e.g., webhook)
    Route::get('hosting-companies/{hostingCompany}/subscriptions', [SubscriptionController::class, 'index']);
    Route::post('hosting-companies/{hostingCompany}/subscriptions', [SubscriptionController::class, 'store']);
    // 8. Property Owners (CRUD)
    Route::resource('property-owners', PropertyOwnerController::class);
    // 9. Properties (CRUD)
    Route::resource('properties', PropertyController::class);
    // 10. Property References (Lookup Data CRUD - Admin only)
    Route::resource('property-references', PropertyReferenceController::class)->except(['show']);
});

// CORE PMS RESOURCES (Full CRUD)
// Route::apiResource('properties', PropertyController::class);
Route::apiResource('room-types', RoomTypeController::class);
Route::apiResource('units', UnitController::class);

// AUXILIARY/LOOKUP RESOURCES (Read-only/Limited CRUD for Admins)
Route::apiResource('amenities', AmenityController::class);
Route::apiResource('property-categories', PropertyCategoryController::class)->only(['index', 'show']);
Route::apiResource('amenity-categories', AmenityCategoryController::class)->only(['index', 'show']);
Route::apiResource('cost-types', CostTypeController::class)->only(['index', 'show']);

// Health Check Endpoint
Route::get('/ping', function () {
    return response()->json([
        'status' => 'ok',
        'message' => 'Hosthome API',
        'api_version' => '1.0.0',
        'build_version' => '1'
    ]);
});

Route::get('test-permissions', function (Request $request) {
    $user = \App\Models\User::where('email', 'jane.admin@hosthome.test')->first();
    // dd($user);
    return response()->json([
        'user_role' => optional($user->role)->name,
        // 1. Check the raw list of permissions (permissions() method)
        'all_permissions' => $user->permissions->toArray(), 
        // 2. Check a specific permission (can() method)
        'can_view_properties' => $user->can('property:view'),
        'can_delete_users' => $user->can('user:delete'), // Should be false
    ]);
});