<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

#[Fillable(['name', 'username', 'email', 'password', 'is_admin', 'can_manage_songs', 'playlist_limit', 'is_active', 'google_id', 'last_login_at'])]
#[Hidden(['password', 'remember_token', 'google_id'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'can_manage_songs' => 'boolean',
            'is_active' => 'boolean',
            'playlist_limit' => 'integer',
        ];
    }

    /** Usuário de login: minúsculas, letras, números, ponto e sublinhado. */
    protected function username(): Attribute
    {
        return Attribute::set(fn (?string $value) => $value === null ? null : Str::lower(trim($value)));
    }

    protected function email(): Attribute
    {
        return Attribute::set(fn (?string $value) => $value === null ? null : Str::lower(trim($value)));
    }

    /** Cadastrar/editar músicas, versões e tags: administradores ou quem recebeu a permissão. */
    public function canManageSongs(): bool
    {
        return $this->is_admin || $this->can_manage_songs;
    }

    /** Limite de playlists: o definido pelo administrador nesta conta ou o padrão; administradores não têm limite. */
    public function playlistLimit(): ?int
    {
        return $this->is_admin ? null : ($this->playlist_limit ?? (int) config('cifras.playlist_limit'));
    }

    public function hasReachedPlaylistLimit(): bool
    {
        $limit = $this->playlistLimit();

        return $limit !== null && $this->playlists()->count() >= $limit;
    }

    public function songs(): HasMany
    {
        return $this->hasMany(Song::class);
    }

    public function tags(): HasMany
    {
        return $this->hasMany(Tag::class);
    }

    public function playlists(): HasMany
    {
        return $this->hasMany(Playlist::class);
    }
}
