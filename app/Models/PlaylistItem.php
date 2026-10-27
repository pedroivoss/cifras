<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['song_id', 'position', 'semitones', 'notes'])]
class PlaylistItem extends Model
{
    use HasFactory;

    protected $touches = ['playlist'];

    protected function casts(): array
    {
        return ['position' => 'integer', 'semitones' => 'integer'];
    }

    public function playlist(): BelongsTo
    {
        return $this->belongsTo(Playlist::class);
    }

    public function song(): BelongsTo
    {
        return $this->belongsTo(Song::class);
    }

    public function key(): ?string
    {
        return $this->song?->keyAfter($this->semitones);
    }
}
