<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Countries (MDB: Global Lookup)
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('iso_code', 2)->nullable();
            $table->string('currency_code', 3)->nullable();
            $table->string('language_code', 5)->nullable();
            $table->decimal('vat_gst_rate', 5, 2)->default(0.00);
            $table->tinyInteger('status')->default(0)->comment('0: Inactive, 1: Active');
            $table->timestamps();
        });

        // 2. Plans (MDB: SaaS Plans)
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->decimal('price_monthly', 8, 2)->nullable();
            $table->json('features')->nullable();
            $table->tinyInteger('status')->default(0)->comment('0: Inactive, 1: Active');
            $table->timestamps();
        });

        // 3. Hosting_Companies (MDB: Top-Level Tenant)
        Schema::create('hosting_companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->foreignId('country_id')->constrained('countries'); // onDelete removed
            $table->string('contact_email')->unique();
            // Production DB Connection Details (Must be populated after provisioning)
            $table->string('db_host')->nullable();
            $table->string('db_name')->nullable();
            $table->string('db_user')->nullable();
            $table->string('db_pass')->nullable()->nullable();
            $table->foreignId('plan_id')->nullable()->constrained('plans'); // onDelete removed
            $table->enum('status', ['active', 'suspended', 'trial'])->default('trial');
            $table->timestamps();
        });

        // 4. Subscriptions (MDB)
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hosting_company_id')->constrained('hosting_companies'); // onDelete removed
            $table->foreignId('plan_id')->constrained('plans'); // onDelete removed
            $table->timestamp('started_at');
            $table->timestamp('expires_at')->nullable();
            $table->enum('status', ['active', 'expired', 'cancelled'])->default('active');
            $table->timestamps();
        });

        // 5. Roles (MDB: RBAC)
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('guard_name');
            $table->timestamps();
        });

        // 6. Permissions (MDB: RBAC)
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('guard_name');
            $table->timestamps();
        });

        // 7. Role_Permission (MDB: RBAC Pivot)
        Schema::create('role_permission', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained('roles'); // onDelete removed
            $table->foreignId('permission_id')->constrained('permissions'); // onDelete removed
            $table->primary(['role_id', 'permission_id']);
        });

        // 8. Users (MDB: Web/Admin Users)
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hosting_company_id')->nullable()->constrained('hosting_companies'); // onDelete removed
            $table->foreignId('role_id')->constrained('roles'); // onDelete removed
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->string('access_token', 64)->nullable()->unique()->index();
            $table->tinyInteger('status')->default(0)->comment('0: Inactive, 1: Active');
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        // 9. Audit_Logs (MDB: System Activity)
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hosting_company_id')->constrained('hosting_companies'); // onDelete removed
            $table->foreignId('user_id')->nullable()->constrained('users'); // onDelete removed
            $table->string('action')->nullable();
            $table->json('context')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->timestamps();
            $table->index('hosting_company_id');
        });

        // 10. System_Settings (CONFIGURATION)
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('data_type')->default('string')->comment('e.g., string, integer, json, boolean');
            $table->timestamps();
        });

        // 11. Property_References (LOOKUP: MUST BE CREATED BEFORE PROPERTIES)
        Schema::create('property_references', function (Blueprint $table) {
            $table->id();
            $table->string('key');
            $table->string('value');
            $table->timestamps();
        });

        // 12. Property_Unit_References (LOOKUP: MUST BE CREATED BEFORE PROPERTY_UNITS)
        Schema::create('property_unit_references', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('value');
            $table->timestamps();
        });

        // 13. Amenities_References (LOOKUP: MUST BE CREATED BEFORE AMENITIES)
        Schema::create('amenities_references', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        // 14. Channels (LOOKUP: MUST BE CREATED BEFORE CHANNELS_MAPPING)
        Schema::create('channels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('external_system_code')->nullable();
            $table->tinyInteger('status')->default(0);
            $table->timestamps();
        });

        // 15. Property_Owners (CORE)
        Schema::create('property_owners', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('email')->unique();
            $table->tinyInteger('status')->default(0);
            $table->foreignId('hosting_company_id');
            $table->timestamps();
        });

        // 16. Properties (CORE: REFERENCES 11, 15)
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_owner_id')->constrained('property_owners'); // onDelete removed
            $table->string('name');
            $table->string('address_line_1')->nullable();
            $table->string('city')->nullable();
            $table->string('zip_code')->nullable();
            $table->string('timezone')->nullable();
            $table->foreignId('property_type_ref_id')->nullable()->constrained('property_references'); // REFERENCES 11
            $table->enum('listing_status', ['draft', 'active', 'archived'])->default('draft');
            $table->tinyInteger('status')->default(0);
            $table->timestamps();
        });

        // 17. Room_Types (CORE: REFERENCES 16)
        Schema::create('room_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained('properties'); // onDelete removed
            $table->string('name')->nullable();
            $table->integer('max_guests')->nullable();
            $table->tinyInteger('status')->default(0);
            $table->timestamps();
        });

        // 18. Room_Type_Photos (REFERENCES 17)
        Schema::create('room_type_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_type_id')->constrained('room_types'); // onDelete removed
            $table->string('url')->nullable();
            $table->string('caption')->nullable();
            $table->boolean('is_main')->default(false);
            $table->integer('sort_order')->default(0);
            $table->tinyInteger('status')->default(0);
            $table->timestamps();
        });

        // 19. Amenities (REFERENCES 13)
        Schema::create('amenities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('amenities_reference_id')->constrained('amenities_references'); // onDelete removed
            $table->string('specific_name')->nullable();
            $table->tinyInteger('status')->default(0);
            $table->timestamps();
        });

        // 20. Room_Type_Amenities (PIVOT: REFERENCES 17, 19)
        Schema::create('room_type_amenities', function (Blueprint $table) {
            $table->foreignId('room_type_id')->constrained('room_types'); // onDelete removed
            $table->foreignId('amenity_id')->constrained('amenities'); // onDelete removed
            $table->primary(['room_type_id', 'amenity_id']);
        });


        // 21. Property_Units (CORE: REFERENCES 17, 12)
        Schema::create('property_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_type_id')->constrained('room_types'); // onDelete removed
            $table->foreignId('unit_type_ref_id')->nullable()->constrained('property_unit_references'); // REFERENCES 12
            $table->string('unit_identifier')->nullable();
            $table->enum('status', ['available', 'maintenance', 'owner_use'])->default('available');
            $table->timestamps();
        });

        // 22. SEO_Metadata (REFERENCES 16, 17, 21 via morphs)
        Schema::create('seo_metadata', function (Blueprint $table) {
            $table->id();
            $table->morphs('model'); // Links to Property, Room Type, or Static Pages
            $table->string('page_slug')->unique()->nullable();
            $table->string('meta_title', 70)->nullable();
            $table->string('meta_description', 160)->nullable();
            $table->string('og_title')->nullable();
            $table->string('og_image_url')->nullable();
            $table->timestamps();
        });

        // 23. Staffers (CORE)
        Schema::create('staffers', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->string('phone')->nullable();
            $table->tinyInteger('status')->default(0);
            $table->timestamps();
        });

        // 24. App_Users (REFERENCES 23)
        Schema::create('app_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staffer_id')->nullable()->constrained('staffers'); // onDelete removed
            $table->string('type')->default('guest');
            $table->string('phone_number')->unique()->nullable();
            $table->string('full_name')->nullable();
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->string('access_token', 64)->nullable()->unique()->index();
            $table->tinyInteger('status')->default(0);
            $table->timestamps();
        });

        // 25. Channel_Accounts (REFERENCES 14, 15)
        Schema::create('channel_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_id')->constrained('channels'); // onDelete removed
            $table->foreignId('property_owner_id')->constrained('property_owners'); // onDelete removed
            $table->string('account_name')->nullable();
            $table->text('api_credentials')->nullable();
            $table->tinyInteger('status')->default(0);
            $table->timestamps();
        });

        // 26. Channels_Mapping (REFERENCES 21, 14)
        Schema::create('channels_mapping', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_unit_id')->constrained('property_units'); // onDelete removed
            $table->foreignId('channel_id')->constrained('channels'); // onDelete removed
            $table->string('external_unit_id')->nullable();
            $table->tinyInteger('status')->default(0);
            $table->timestamps();
            $table->unique(['property_unit_id', 'channel_id']);
        });

        // 27. Amenities_Mapping (REFERENCES 14, 19)
        Schema::create('amenities_mapping', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_id')->constrained('channels'); // onDelete removed
            $table->foreignId('amenity_id')->constrained('amenities'); // onDelete removed
            $table->string('channel_code')->nullable();
            $table->timestamps();
            $table->unique(['channel_id', 'amenity_id']);
        });

        // 28. Guests (CORE)
        Schema::create('guests', function (Blueprint $table) {
            $table->id();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->string('access_token', 64)->nullable()->unique()->index();
            $table->timestamps();
        });

        // 29. Owner_Statements (REFERENCES 15)
        Schema::create('owner_statements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_owner_id')->constrained('property_owners'); // onDelete removed
            $table->date('period_start_date')->nullable();
            $table->date('period_end_date')->nullable();
            $table->decimal('total_revenue', 10, 2)->nullable();
            $table->decimal('total_fees', 10, 2)->nullable();
            $table->decimal('net_payout_amount', 10, 2)->nullable();
            $table->enum('payout_status', ['pending', 'paid', 'failed'])->default('pending');
            $table->timestamp('payout_date')->nullable();
            $table->string('document_url')->nullable();
            $table->timestamps();
            $table->unique(['property_owner_id', 'period_start_date']);
        });

        // 30. Bookings (REFERENCES 21, 28, 29)
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_unit_id')->constrained('property_units'); // onDelete removed
            $table->foreignId('guest_id')->constrained('guests'); // onDelete removed
            $table->foreignId('owner_statement_id')->nullable()->constrained('owner_statements'); // onDelete removed
            $table->string('channel_source')->nullable();
            $table->string('external_reservation_id')->nullable()->unique();
            $table->string('channel_booking_id')->nullable();
            $table->timestamp('check_in_date');
            $table->timestamp('check_out_date');
            $table->decimal('total_price', 10, 2);
            $table->integer('guest_count')->default(1);
            $table->integer('adult_count')->default(1);
            $table->enum('status', ['confirmed', 'cancelled', 'checked_in', 'checked_out'])->default('confirmed');
            $table->timestamps();
        });

        // 31. Payments (REFERENCES 30)
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings'); // onDelete removed
            $table->decimal('amount', 10, 2);
            $table->string('payment_method')->nullable();
            $table->string('payment_gateway')->nullable();
            $table->enum('type', ['deposit', 'full_payment', 'refund', 'host_payout']);
            $table->string('transaction_id')->unique();
            $table->enum('reconciliation_status', ['pending', 'matched', 'unmatched'])->default('pending');
            $table->tinyInteger('status')->default(0)->comment('0: Pending, 1: Success, 2: Failed');
            $table->timestamps();
        });

        // 32. Blocking_Events (REFERENCES 21)
        Schema::create('blocking_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_unit_id')->constrained('property_units'); // onDelete removed
            $table->string('reason')->nullable();
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->timestamps();
        });

        // 33. Tasks (REFERENCES 30, 23, 21)
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->nullable()->constrained('bookings'); // onDelete removed
            $table->foreignId('staffer_id')->nullable()->constrained('staffers'); // onDelete removed
            $table->foreignId('property_unit_id')->constrained('property_units'); // onDelete removed
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'completed', 'verified'])->default('pending');
            $table->json('sop_steps')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamps();
        });

        // 34. Messages (REFERENCES 30, 28)
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings'); // onDelete removed
            $table->foreignId('guest_id')->constrained('guests'); // onDelete removed
            $table->enum('direction', ['inbound', 'outbound']);
            $table->string('channel')->nullable();
            $table->text('body')->nullable();
            $table->boolean('is_template')->default(false);
            $table->timestamps();
        });

        // 35. Message_Templates
        Schema::create('message_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('subject')->nullable();
            $table->text('content')->nullable();
            $table->enum('channel_type', ['email', 'sms', 'whatsapp', 'internal']);
            $table->string('trigger_event')->nullable();
            $table->tinyInteger('status')->default(0);
            $table->timestamps();
        });

        // 36. Pricing_Rules (REFERENCES 17)
        Schema::create('pricing_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_type_id')->constrained('room_types'); // onDelete removed
            $table->string('name');
            $table->enum('rule_type', ['base', 'markup', 'seasonal', 'event', 'last_minute']);
            $table->decimal('price_modifier', 8, 2)->nullable();
            $table->enum('modifier_type', ['fixed', 'percentage', 'fixed_price']);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('day_of_week')->nullable();
            $table->tinyInteger('status')->default(0);
            $table->timestamps();
        });

        // 37. Daily_Availability_Price (REFERENCES 21)
        Schema::create('daily_availability_price', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_unit_id')->constrained('property_units'); // onDelete removed
            $table->date('date');
            $table->decimal('base_rate', 10, 2);
            $table->integer('min_stay')->default(1);
            $table->integer('max_stay')->nullable();
            $table->boolean('is_available')->default(true);
            $table->boolean('closed_to_arrival')->default(false);
            $table->boolean('closed_to_departure')->default(false);
            $table->enum('sync_status', ['pending', 'synced', 'error'])->default('pending');
            $table->timestamps();
            $table->unique(['property_unit_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reversing the order of table drops to respect foreign key constraints
        Schema::dropIfExists('daily_availability_price');
        Schema::dropIfExists('pricing_rules');
        Schema::dropIfExists('message_templates');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('tasks');
        Schema::dropIfExists('blocking_events');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('owner_statements');
        Schema::dropIfExists('guests');
        Schema::dropIfExists('seo_metadata');
        Schema::dropIfExists('amenities_mapping');
        Schema::dropIfExists('room_type_amenities');
        Schema::dropIfExists('amenities');
        Schema::dropIfExists('amenities_references');
        Schema::dropIfExists('channels_mapping');
        Schema::dropIfExists('channel_accounts');
        Schema::dropIfExists('channels');
        Schema::dropIfExists('app_users');
        Schema::dropIfExists('staffers');
        Schema::dropIfExists('property_units');
        Schema::dropIfExists('room_type_photos');
        Schema::dropIfExists('room_types');
        Schema::dropIfExists('properties');
        Schema::dropIfExists('system_settings');
        Schema::dropIfExists('property_owners');
        Schema::dropIfExists('property_unit_references');
        Schema::dropIfExists('property_references');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('users');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('role_permission');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('hosting_companies');
        Schema::dropIfExists('plans');
        Schema::dropIfExists('countries');
    }
};
