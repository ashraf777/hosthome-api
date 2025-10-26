<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\HostingCompany;
use App\Models\Permission;
use App\Models\Plan;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class MasterDatabaseSeeder extends Seeder
{
    /**
     * Run the Master Database seeders.
     */
    public function run(): void
    {
        // --- TEMPORARILY DISABLE FOREIGN KEY CHECKS ---
        // This is necessary to allow TRUNCATE operations on tables with existing FK relationships.
        Schema::disableForeignKeyConstraints();

        // --- SAFE TRUNCATE LOOKUP TABLES (Clear Child Tables FIRST) ---
        if (Schema::hasTable('role_permission')) { DB::table('role_permission')->truncate(); } // PIVOT TABLE CLEARED FIRST
        if (Schema::hasTable('users')) { DB::table('users')->truncate(); }
        if (Schema::hasTable('hosting_companies')) { DB::table('hosting_companies')->truncate(); }
        
        // Clear Parent/Lookup Tables
        if (Schema::hasTable('permissions')) { DB::table('permissions')->truncate(); }
        if (Schema::hasTable('roles')) { DB::table('roles')->truncate(); }
        if (Schema::hasTable('countries')) { DB::table('countries')->truncate(); }
        if (Schema::hasTable('plans')) { DB::table('plans')->truncate(); }
        
        // --- RE-ENABLE FOREIGN KEY CHECKS ---
        Schema::enableForeignKeyConstraints();

        // 1. Setup Lookup Data (Countries and Plans)
        $country = Country::create([
            'name' => 'Malaysia',
            'iso_code' => 'MY',
            'currency_code' => 'MYR',
            'language_code' => 'en-MY',
            'vat_gst_rate' => 0.00,
            'status' => 1,
        ]);

        $basicPlan = Plan::create([
            'name' => 'Basic Host',
            'price_monthly' => 49.00,
            'features' => json_encode(['max_properties' => 5, 'ota_sync' => 2]),
            'status' => 1,
        ]);
        
        // 2. Setup Permissions Blueprint (Required System Actions)
        $permissions = [
            // Platform Management (Super Admin)
            ['name' => 'platform:manage', 'guard_name' => 'web'],
            // Role Management
            ['name' => 'role:view', 'guard_name' => 'web'],
            ['name' => 'role:create', 'guard_name' => 'web'],
            ['name' => 'role:update', 'guard_name' => 'web'],
            ['name' => 'role:assign-permission', 'guard_name' => 'web'],
            // User Management
            ['name' => 'user:view', 'guard_name' => 'web'],
            ['name' => 'user:create', 'guard_name' => 'web'],
            ['name' => 'user:update', 'guard_name' => 'web'],
            ['name' => 'user:assign-role', 'guard_name' => 'web'],
            // Property Management
            ['name' => 'property:view', 'guard_name' => 'web'],
            ['name' => 'property:create', 'guard_name' => 'web'],
            ['name' => 'property:update', 'guard_name' => 'web'],
            // Booking Management
            ['name' => 'booking:view', 'guard_name' => 'web'],
            ['name' => 'booking:create', 'guard_name' => 'web'],
            ['name' => 'booking:update', 'guard_name' => 'web'],
            // Owner/Payout Management
            ['name' => 'owner:view', 'guard_name' => 'web'],
            ['name' => 'owner:create', 'guard_name' => 'web'],
        ];

        DB::table('permissions')->insert($permissions);
        
        // Retrieve all IDs after insertion
        $allPermissions = Permission::pluck('id');
        $propertyPermissions = Permission::whereIn('name', ['property:view', 'property:create', 'property:update'])->pluck('id');

        // 3. Setup Roles (No hosting_company_id specified, as the column is missing)
        $adminRole = Role::create(['name' => 'Super Admin', 'guard_name' => 'web']);
        $managerRole = Role::create(['name' => 'Host Manager', 'guard_name' => 'web']); 
        $staffRole = Role::create(['name' => 'Staff/Cleaner', 'guard_name' => 'web']); 

        // 4. Assign Permissions to Roles (Role_Permission Pivot)
        
        // Super Admin: Has all permissions
        foreach ($allPermissions as $permissionId) {
            DB::table('role_permission')->insert(['role_id' => $adminRole->id, 'permission_id' => $permissionId]);
        }
        
        // Host Manager: Has Property and Booking permissions
        foreach ($propertyPermissions as $permissionId) {
            DB::table('role_permission')->insert(['role_id' => $managerRole->id, 'permission_id' => $permissionId]);
        }

        // 5. Create Default Hosting Company (Tenant)
        $company = HostingCompany::create([
            'name' => 'Default Testing Host',
            'slug' => 'default-test-host',
            'country_id' => $country->id,
            'contact_email' => 'contact@hosthome.test',
            'db_host' => env('DB_HOST', '127.0.0.1'),
            'db_name' => 'hosthome_test_tenant', // Placeholder for tenant DB name
            'db_user' => 'tenant_user',
            'plan_id' => $basicPlan->id,
            'status' => 'active',
        ]);

        // 6. Update Roles with correct Hosting Company ID
        // *** REMOVED: Role::whereIn('name', ['Host Manager', 'Staff/Cleaner'])->update(['hosting_company_id' => $company->id]); ***
        // This update will fail because the column is missing in the database schema.

        // 7. Create Default Admin User (Requires activation in the DB)
        User::create([
            'name' => 'Default Host Admin',
            'email' => 'host.admin@test.com',
            'password' => Hash::make('password'), 
            'access_token' => Str::random(64),
            'hosting_company_id' => $company->id,
            'role_id' => $managerRole->id, // Assign the Host Manager Role
            'status' => 1, // Set to active for immediate login testing
        ]);
        
        // Create an inactive user to test registration/activation flow
         User::create([
            'name' => 'Inactive Test User',
            'email' => 'inactive.user@test.com',
            'password' => Hash::make('password'), 
            'access_token' => Str::random(64),
            'hosting_company_id' => $company->id,
            'role_id' => $staffRole->id, 
            'status' => 0, 
        ]);
    }
}
