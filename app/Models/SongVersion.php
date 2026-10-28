<?php

namespace App\Models;

use App\Services\Chords\Key;
use Database\Factories\SongVersionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Uma versão de uma música (Completa, Simplificada...). Os acordes estão escritos em `original_key`;
 * com capotraste na casa `capo`, o tom que soa é `original_key` + `capo` semitons.
 */
#[Fillable(['name', 'original_key', 'capo', 'content', 'position'])]
class SongVersion extends Model
{
    /** @use HasFactory<SongVersionFactory> */
    use HasFactory;

    public const DEFAULT_NAME = 'Principal';

    protected $touches = ['song'];

    protected function casts(): array
    {
        return ['capo' => 'integer', 'position' => 'integer'];
    }

    public function song(): BelongsTo
    {
        return $this->belongsTo(Song::class);
    }

    public function playlistItems(): HasMany
    {
        return $this->hasMany(PlaylistItem::class);
    }

    /** Tom dos acordes escritos após transpor. */
    public function keyAfter(int $semitones): ?string
    {
        return $this->original_key ? Key::transpose($this->original_key, $semitones) : null;
    }

    /** Tom que soa com capotraste (null quando não há capo ou tom definido). */
    public function soundingKeyAfter(int $semitones, ?int $capo = null): ?string
    {
        $capo ??= $this->capo;

        return $capo > 0 && $this->original_key ? Key::transpose($this->original_key, $semitones + $capo) : null;
    }
}
