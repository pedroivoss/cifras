<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['song_id', 'song_version_id', 'position', 'semitones', 'capo', 'notes'])]
class PlaylistItem extends Model
{
    use HasFactory;

    protected $touches = ['playlist'];

    protected function casts(): array
    {
        return ['position' => 'integer', 'semitones' => 'integer', 'capo' => 'integer'];
    }

    public function playlist(): BelongsTo
    {
        return $this->belongsTo(Playlist::class);
    }

    public function song(): BelongsTo
    {
        return $this->belongsTo(Song::class);
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(SongVersion::class, 'song_version_id');
    }

    /** Versão usada na exibição/PDF: a escolhida no item ou, na falta, a primeira da música. */
    public function sheetVersion(): SongVersion
    {
        return $this->version ?? $this->song->defaultVersion();
    }

    /** Capotraste do item; se não definido, o da versão. */
    public function effectiveCapo(): int
    {
        return $this->capo ?? $this->sheetVersion()->capo;
    }

    public function key(): ?string
    {
        return $this->sheetVersion()->keyAfter($this->semitones);
    }

    public function soundingKey(): ?string
    {
        return $this->sheetVersion()->soundingKeyAfter($this->semitones, $this->effectiveCapo());
    }
}
