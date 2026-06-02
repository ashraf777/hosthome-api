<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Creates the user_notifications table for in-app cleaner notifications.
     */
    public function up(): void
    {
        Schema::create('user_notifications', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('cascade')
                  ->comment('The cleaner (user) this notification is for');

            $table->string('title');
            $table->text('body');

            $table->enum('type', ['new_task', 'task_update', 'general'])
                  ->default('general');

            $table->unsignedBigInteger('reference_id')->nullable()
                  ->comment('e.g., task_id — for deep-linking to specific resource');

            $table->boolean('is_read')->default(false);

            $table->timestamps();

            $table->index(['user_id', 'is_read']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_notifications');
    }
};
