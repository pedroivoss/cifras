<?php

namespace App\Models;

use App\Services\Chords\Key;
use App\Support\YouTube;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['title', 'artist', 'original_key', 'content', 'youtube_url', 'source', 'source_url', 'notes'])]
class Song extends Model
{
    use HasFactory;

    public const SOURCES = [
        'manual' => 'Cadastro manual',
        'colagem' => 'Colagem de texto',
        'arquivo' => 'Arquivo (texto/ChordPro)',
        'cifraclub' => 'Cifra Club (salva pelo navegador)',
        'cifraclub-api' => 'cifraclub-api (externa)',
        'outro' => 'Outra fonte',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class)->orderBy('name');
    }

    public function playlistItems(): HasMany
    {
        return $this->hasMany(PlaylistItem::class);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        $like = '%'.addcslashes($term, '%_\\').'%';

        return $query->where(fn (Builder $q) => $q->where('title', 'like', $like)->orWhere('artist', 'like', $like));
    }

    /** Filtra músicas que possuem TODAS as tags informadas. */
    public function scopeWithAllTags(Builder $query, array $tagIds): Builder
    {
        foreach (array_filter(array_map('intval', $tagIds)) as $tagId) {
            $query->whereHas('tags', fn (Builder $q) => $q->whereKey($tagId));
        }

        return $query;
    }

    public function keyAfter(int $semitones): ?string
    {
        return $this->original_key ? Key::transpose($this->original_key, $semitones) : null;
    }

    public function youtubeId(): ?string
    {
        return YouTube::videoId($this->youtube_url);
    }
}
