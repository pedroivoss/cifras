<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cifra, tom e capotraste saem de `songs` e vão para `song_versions` (Completa, Simplificada...).
 * Cada música existente vira uma versão "Principal"; itens de playlist passam a apontar para a versão.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('song_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('song_id')->constrained()->cascadeOnDelete();
            $table->string('name', 60)->default('Principal');
            $table->string('original_key', 8)->nullable();
            $table->unsignedTinyInteger('capo')->default(0);
            $table->longText('content');
            $table->unsignedInteger('position')->default(1);
            $table->timestamps();

            $table->index(['song_id', 'position']);
        });

        DB::statement(
            "INSERT INTO song_versions (song_id, name, original_key, capo, content, position, created_at, updated_at)
             SELECT id, 'Principal', original_key, 0, content, 1, created_at, updated_at FROM songs"
        );

        Schema::table('playlist_items', function (Blueprint $table) {
            $table->foreignId('song_version_id')->nullable()->after('song_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('capo')->nullable()->after('semitones');
        });

        DB::statement(
            'UPDATE playlist_items SET song_version_id = (
                SELECT MIN(song_versions.id) FROM song_versions WHERE song_versions.song_id = playlist_items.song_id
            )'
        );

        Schema::table('songs', function (Blueprint $table) {
            $table->dropColumn(['content', 'original_key']);
        });
    }

    public function down(): void
    {
        Schema::table('songs', function (Blueprint $table) {
            $table->string('original_key', 8)->nullable()->after('artist');
            $table->longText('content')->nullable()->after('original_key');
        });

        DB::statement(
            'UPDATE songs SET
                content = (SELECT content FROM song_versions WHERE song_versions.song_id = songs.id ORDER BY position, id LIMIT 1),
                original_key = (SELECT original_key FROM song_versions WHERE song_versions.song_id = songs.id ORDER BY position, id LIMIT 1)'
        );

        Schema::table('playlist_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('song_version_id');
            $table->dropColumn('capo');
        });

        Schema::dropIfExists('song_versions');
    }
};
