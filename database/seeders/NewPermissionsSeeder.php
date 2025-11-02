<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Permission; // Corrected namespace

class NewPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // Property Management
            ['name' => 'property:delete', 'guard_name' => 'web'],
            // Room Type Management
            ['name' => 'room-type:view', 'guard_name' => 'web'],
            ['name' => 'room-type:create', 'guard_name' => 'web'],
            ['name' => 'room-type:update', 'guard_name' => 'web'],
            ['name' => 'room-type:delete', 'guard_name' => 'web'],
            // Room Type Photo Management
            ['name' => 'room-type-photo:view', 'guard_name' => 'web'],
            ['name' => 'room-type-photo:create', 'guard_name' => 'web'],
            ['name' => 'room-type-photo:update', 'guard_name' => 'web'],
            ['name' => 'room-type-photo:delete', 'guard_name' => 'web'],
            // Unit Management
            ['name' => 'unit:view', 'guard_name' => 'web'],
            ['name' => 'unit:create', 'guard_name' => 'web'],
            ['name' => 'unit:update', 'guard_name' => 'web'],
            ['name' => 'unit:delete', 'guard_name' => 'web'],
            // Amenity Management
            ['name' => 'amenity:view', 'guard_name' => 'web'],
            ['name' => 'amenity:create', 'guard_name' => 'web'],
            ['name' => 'amenity:update', 'guard_name' => 'web'],
            ['name' => 'amenity:delete', 'guard_name' => 'web'],
            // Pricing & Availability Management
            ['name' => 'pricing-rule:view', 'guard_name' => 'web'],
            ['name' => 'pricing-rule:create', 'guard_name' => 'web'],
            ['name' => 'pricing-rule:update', 'guard_name' => 'web'],
            ['name' => 'pricing-rule:delete', 'guard_name' => 'web'],
            ['name' => 'availability:view', 'guard_name' => 'web'],
            // Channel Management
            ['name' => 'channel:view', 'guard_name' => 'web'],
            ['name' => 'channel:create', 'guard_name' => 'web'],
            ['name' => 'channel:update', 'guard_name' => 'web'],
            ['name' => 'channel:delete', 'guard_name' => 'web'],
            // SEO Management
            ['name' => 'seo:manage', 'guard_name' => 'web'],
            // Booking Management
            ['name' => 'booking:delete', 'guard_name' => 'web'],
            // Owner/Payout Management
            ['name' => 'owner:update', 'guard_name' => 'web'],
            ['name' => 'owner:delete', 'guard_name' => 'web'],
        ];

        // Insert new permissions and ignore if they already exist
        Permission::insertOrIgnore($permissions);
    }
}
