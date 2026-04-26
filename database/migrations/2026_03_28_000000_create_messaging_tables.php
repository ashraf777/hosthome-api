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
        Schema::create('message_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('trigger_event')->default('manual'); // manual, pre-check-in, post-check-out, booking-confirmed
            $table->integer('offset_hours')->default(0); // hours before/after trigger event
            $table->string('subject')->nullable();
            $table->text('body');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guest_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('message_template_id')->nullable()->constrained('message_templates')->nullOnDelete();
            
            $table->string('direction')->default('outbound'); // inbound, outbound
            $table->string('channel')->default('email'); // beds24, email, whatsapp, magic-link
            $table->string('external_message_id')->nullable()->unique(); // For Beds24/Channel sync
            
            $table->string('subject')->nullable();
            $table->text('content');
            
            $table->string('status')->default('sent'); // pending, sent, failed, delivered, read
            $table->timestamp('sent_at')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
        Schema::dropIfExists('message_templates');
    }
};
