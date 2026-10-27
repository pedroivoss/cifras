<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

#[Fillable(['name', 'slug', 'color'])]
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function songs(): BelongsToMany
    {
        return $this->belongsToMany(Song::class);
    }
}
