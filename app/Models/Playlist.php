<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable(['name', 'description', 'event_date', 'cover_title', 'cover_image', 'cover_quote', 'is_public', 'allow_public_pdf'])]
class Playlist extends Model
{
    use HasFactory;

    /** Imagens de capa ficam no disco privado e são servidas por rota (dono ou link público). */
    public const COVER_DISK = 'local';

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

        static::deleted(function (Playlist $playlist) {
            $playlist->deleteCoverImage();
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

    /** Capa personalizada da missa: título, imagem ou passagem. */
    public function hasCustomCover(): bool
    {
        return filled($this->cover_title) || filled($this->cover_image) || filled($this->cover_quote);
    }

    public function coverHeading(): string
    {
        return filled($this->cover_title) ? $this->cover_title : $this->name;
    }

    public function coverImagePath(): ?string
    {
        return $this->cover_image && Storage::disk(self::COVER_DISK)->exists($this->cover_image)
            ? Storage::disk(self::COVER_DISK)->path($this->cover_image)
            : null;
    }

    /** Imagem embutida (data URI) para o PDF, que não acessa arquivos nem URLs externas. */
    public function coverImageDataUri(): ?string
    {
        $path = $this->coverImagePath();

        return $path ? 'data:'.mime_content_type($path).';base64,'.base64_encode((string) file_get_contents($path)) : null;
    }

    public function deleteCoverImage(): void
    {
        if ($this->cover_image) {
            Storage::disk(self::COVER_DISK)->delete($this->cover_image);
        }
    }

    public function nextPosition(): int
    {
        return (int) $this->items()->max('position') + 1;
    }

    public function duplicate(): Playlist
    {
        return DB::transaction(function () {
            $copy = $this->replicate(['share_token', 'is_public', 'allow_public_pdf', 'cover_image']);
            $copy->name = mb_substr($this->name.' (cópia)', 0, 255);
            $copy->is_public = false;
            $copy->allow_public_pdf = false;

            if ($this->coverImagePath()) {
                $copy->cover_image = 'covers/'.Str::random(40).'.'.pathinfo($this->cover_image, PATHINFO_EXTENSION);
                Storage::disk(self::COVER_DISK)->copy($this->cover_image, $copy->cover_image);
            }

            $copy->save();

            foreach ($this->items as $item) {
                $copy->items()->create($item->only(PlaylistItem::COPYABLE));
            }

            return $copy;
        });
    }
}
