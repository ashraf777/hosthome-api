<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Creates the task_media table for cleaner proof-of-work uploads.
     */
    public function up(): void
    {
        Schema::create('task_media', function (Blueprint $table) {
            $table->id();

            $table->foreignId('task_id')
                  ->constrained('tasks')
                  ->comment('The task this media proof belongs to');

            $table->foreignId('uploaded_by')
                  ->constrained('users')
                  ->comment('The cleaner (user) who uploaded this media');

            $table->string('media_path')
                  ->comment('Relative path on disk: task-media/{task_id}/{filename}');

            $table->enum('media_type', ['image', 'video'])
                  ->default('image');

            $table->text('note')->nullable()
                  ->comment('Optional note or comment from the cleaner');

            $table->enum('status_at_upload', ['In Progress', 'Paused', 'Completed'])
                  ->comment('Task status at the time this media was uploaded');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_media');
    }
};
