<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

#[Fillable(['name', 'slug', 'color', 'user_id'])]
class Tag extends Model
{
    use HasFactory;

    public const DEFAULTS = [
        'Canto de Entrada', 'Ato Penitencial', 'Glória', 'Salmo', 'Aclamação', 'Ofertório', 'Santo',
        'Comunhão', 'Ação de Graças', 'Canto Final', 'Adoração', 'Louvor', 'Outros',
    ];

    protected static function booted(): void
    {
        static::saving(fn (Tag $tag) => $tag->slug = Str::slug($tag->name));
    }

    /** Tags iniciais da biblioteca (idempotente). */
    public static function seedDefaults(?User $creator = null): void
    {
        foreach (self::DEFAULTS as $name) {
            self::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name, 'user_id' => $creator?->id]);
        }
    }

    /** Quem criou a tag (as tags são da biblioteca, compartilhadas por todos). */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function songs(): BelongsToMany
    {
        return $this->belongsToMany(Song::class);
    }
}
