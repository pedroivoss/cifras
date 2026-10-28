<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

#[Fillable(['name', 'description', 'event_date', 'is_public', 'allow_public_pdf'])]
class Playlist extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'is_public' => 'boolean',
            'allow_public_pdf' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Playlist $playlist) {
            $playlist->share_token ??= self::newShareToken();
        });
    }

    /** 40 caracteres hex de random_bytes (160 bits): não previsível. */
    public static function newShareToken(): string
    {
        return bin2hex(random_bytes(20));
    }

    public function regenerateShareToken(): void
    {
        $this->forceFill(['share_token' => self::newShareToken()])->save();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PlaylistItem::class)->orderBy('position')->orderBy('id');
    }

    public function slug(): string
    {
        return Str::slug($this->name) ?: 'playlist-'.$this->id;
    }

    public function pdfFilename(): string
    {
        return 'repertorio-'.$this->slug().'.pdf';
    }

    public function docxFilename(): string
    {
        return 'repertorio-'.$this->slug().'.docx';
    }

    public function nextPosition(): int
    {
        return (int) $this->items()->max('position') + 1;
    }

    public function duplicate(): Playlist
    {
        return DB::transaction(function () {
            $copy = $this->replicate(['share_token', 'is_public', 'allow_public_pdf']);
            $copy->name = mb_substr($this->name.' (cópia)', 0, 255);
            $copy->is_public = false;
            $copy->allow_public_pdf = false;
            $copy->save();

            foreach ($this->items as $item) {
                $copy->items()->create($item->only(['song_id', 'position', 'semitones', 'notes']));
            }

            return $copy;
        });
    }
}
