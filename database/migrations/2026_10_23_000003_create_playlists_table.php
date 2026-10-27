<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('playlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->date('event_date')->nullable();
            $table->string('share_token', 64)->unique();
            $table->boolean('is_public')->default(false);
            $table->boolean('allow_public_pdf')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'updated_at']);
        });

        // Cada item tem ID próprio: a mesma música pode aparecer várias vezes, com tons diferentes.
        Schema::create('playlist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('playlist_id')->constrained()->cascadeOnDelete();
            $table->foreignId('song_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->smallInteger('semitones')->default(0);
            $table->string('notes', 500)->nullable();
            $table->timestamps();

            $table->index(['playlist_id', 'position']);
            $table->index('song_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('playlist_items');
        Schema::dropIfExists('playlists');
    }
};
