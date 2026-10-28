<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Folheto da missa: capa opcional (título, imagem, passagem), itens de texto (leituras, salmo, avisos)
 * entre as músicas e ajuste da cifra só naquele item, sem alterar a música da biblioteca.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('playlists', function (Blueprint $table) {
            $table->string('cover_title')->nullable()->after('event_date');
            $table->string('cover_image')->nullable()->after('cover_title');
            $table->text('cover_quote')->nullable()->after('cover_image');
        });

        Schema::table('playlist_items', function (Blueprint $table) {
            $table->string('kind', 10)->default('song')->after('playlist_id');
            $table->foreignId('song_id')->nullable()->change();
            $table->string('title')->nullable()->after('notes');
            $table->longText('body')->nullable()->after('title');
            $table->longText('content')->nullable()->after('body');
        });
    }

    public function down(): void
    {
        DB::table('playlist_items')->whereNull('song_id')->delete();

        Schema::table('playlist_items', function (Blueprint $table) {
            $table->dropColumn(['kind', 'title', 'body', 'content']);
            $table->foreignId('song_id')->nullable(false)->change();
        });

        Schema::table('playlists', function (Blueprint $table) {
            $table->dropColumn(['cover_title', 'cover_image', 'cover_quote']);
        });
    }
};
