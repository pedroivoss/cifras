<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['kind', 'song_id', 'song_version_id', 'position', 'semitones', 'capo', 'notes', 'title', 'body', 'content'])]
class PlaylistItem extends Model
{
    use HasFactory;

    public const KIND_SONG = 'song';

    /** Texto livre da missa (leitura, salmo, aviso): sem música, não entra na numeração. */
    public const KIND_TEXT = 'text';

    /** Campos copiados ao duplicar a playlist. */
    public const COPYABLE = ['kind', 'song_id', 'song_version_id', 'position', 'semitones', 'capo', 'notes', 'title', 'body', 'content'];

    protected $attributes = ['kind' => self::KIND_SONG];

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

    public function isText(): bool
    {
        return $this->kind === self::KIND_TEXT;
    }

    /** A cifra foi ajustada só nesta playlist (a música da biblioteca não muda). */
    public function hasCustomContent(): bool
    {
        return $this->content !== null;
    }

    /** Cifra usada neste item: o ajuste da playlist ou o conteúdo da versão. */
    public function sheetContent(): string
    {
        return $this->content ?? $this->sheetVersion()->content;
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
