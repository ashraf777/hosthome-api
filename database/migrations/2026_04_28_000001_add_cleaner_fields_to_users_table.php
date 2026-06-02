<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds cleaner app support fields to the users table.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('availability_status', ['available', 'unavailable'])
                  ->default('available')
                  ->after('status')
                  ->comment('Used by cleaner app to toggle availability');

            $table->string('fcm_token')->nullable()
                  ->after('availability_status')
                  ->comment('Firebase Cloud Messaging device token for push notifications');

            $table->string('login_pin', 6)->nullable()
                  ->after('fcm_token')
                  ->comment('Bcrypt-hashed 4-digit PIN for cleaner app login');

            $table->timestamp('pin_expires_at')->nullable()
                  ->after('login_pin')
                  ->comment('Expiry timestamp for the login PIN (10 min TTL)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['availability_status', 'fcm_token', 'login_pin', 'pin_expires_at']);
        });
    }
};
