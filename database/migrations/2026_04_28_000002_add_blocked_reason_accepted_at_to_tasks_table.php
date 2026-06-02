<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds cleaner app status tracking fields to the tasks table.
     */
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->text('blocked_reason')->nullable()
                  ->after('remarks')
                  ->comment('Reason provided by cleaner when status is set to Paused');

            $table->timestamp('accepted_at')->nullable()
                  ->after('blocked_reason')
                  ->comment('Timestamp when a cleaner first started the task (moved to In Progress)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn(['blocked_reason', 'accepted_at']);
        });
    }
};
