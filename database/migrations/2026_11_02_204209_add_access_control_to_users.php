<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Multiusuário: contas criadas só pelo administrador, login por e-mail ou usuário (ou Google),
 * ativar/desativar, permissão para cadastrar músicas. Músicas e tags viram uma biblioteca única,
 * compartilhada por todos; user_id passa a indicar quem cadastrou (e não apaga a música se a conta sumir).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 40)->nullable()->unique()->after('name');
            $table->boolean('is_admin')->default(false)->after('password');
            $table->boolean('can_manage_songs')->default(false)->after('is_admin');
            $table->boolean('is_active')->default(true)->after('can_manage_songs');
            $table->string('google_id')->nullable()->unique()->after('is_active');
            $table->timestamp('last_login_at')->nullable()->after('google_id');
            $table->string('password')->nullable()->change();
        });

        // Contas existentes são do dono do sistema: viram administradores com permissão de músicas.
        $taken = [];
        foreach (DB::table('users')->orderBy('id')->get(['id', 'email']) as $user) {
            $base = Str::limit(preg_replace('/[^a-z0-9._]/', '', Str::lower(Str::before($user->email, '@'))) ?: 'usuario', 34, '');
            $username = $base;
            for ($i = 2; in_array($username, $taken, true); $i++) {
                $username = $base.$i;
            }
            $taken[] = $username;

            DB::table('users')->where('id', $user->id)->update(['username' => $username, 'is_admin' => true, 'can_manage_songs' => true]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 40)->nullable(false)->change();
        });

        Schema::table('songs', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });
        Schema::table('songs', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });

        $this->mergeDuplicateTags();

        Schema::table('tags', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropUnique(['user_id', 'slug']);
        });
        Schema::table('tags', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->unique('slug');
        });
    }

    public function down(): void
    {
        $owner = DB::table('users')->orderBy('id')->value('id');
        DB::table('songs')->whereNull('user_id')->update(['user_id' => $owner]);
        DB::table('tags')->whereNull('user_id')->update(['user_id' => $owner]);
        DB::table('users')->whereNull('password')->update(['password' => Hash::make(Str::random(40))]);

        Schema::table('tags', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropUnique(['slug']);
        });
        Schema::table('tags', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable(false)->change();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique(['user_id', 'slug']);
        });

        Schema::table('songs', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });
        Schema::table('songs', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable(false)->change();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropUnique(['google_id']);
            $table->dropColumn(['username', 'is_admin', 'can_manage_songs', 'is_active', 'google_id', 'last_login_at']);
            $table->string('password')->nullable(false)->change();
        });
    }

    /** Tags com o mesmo nome em contas diferentes viram uma só (as músicas passam para a que fica). */
    private function mergeDuplicateTags(): void
    {
        $duplicates = DB::table('tags')->select('slug', DB::raw('MIN(id) as keep_id'))->groupBy('slug')->havingRaw('COUNT(*) > 1')->get();

        foreach ($duplicates as $duplicate) {
            $others = DB::table('tags')->where('slug', $duplicate->slug)->where('id', '!=', $duplicate->keep_id)->pluck('id');

            foreach (DB::table('song_tag')->whereIn('tag_id', $others)->pluck('song_id') as $songId) {
                DB::table('song_tag')->insertOrIgnore(['song_id' => $songId, 'tag_id' => $duplicate->keep_id]);
            }

            DB::table('tags')->whereIn('id', $others)->delete();
        }
    }
};
