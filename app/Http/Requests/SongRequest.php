<?php

namespace App\Http\Requests;

use App\Models\Song;
use App\Support\YouTube;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SongRequest extends FormRequest
{
    public function authorize(): bool
    {
        $song = $this->route('song');

        return $song === null || $this->user()->can('update', $song);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $song = $this->route('song');

        return [
            'title' => ['required', 'string', 'max:255'],
            'artist' => ['nullable', 'string', 'max:255'],
            ...SongVersionRequest::fieldRules(),
            'version_id' => ['nullable', 'integer', Rule::exists('song_versions', 'id')->where('song_id', $song?->id ?? 0)],
            'youtube_url' => ['nullable', 'string', 'max:500', function (string $attribute, mixed $value, Closure $fail) {
                if (! YouTube::isValidUrl($value)) {
                    $fail('Informe um link válido do YouTube (youtube.com ou youtu.be).');
                }
            }],
            'source' => ['required', Rule::in(array_keys(Song::SOURCES))],
            'source_url' => ['nullable', 'url:http,https', 'max:500'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'tags' => ['array'],
            'tags.*' => ['integer', Rule::exists('tags', 'id')->where('user_id', $this->user()->id)],
            'new_tags' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Campos da música (sem os da versão nem as tags).
     *
     * @return array<string, mixed>
     */
    public function songAttributes(): array
    {
        return $this->safe()->only(['title', 'artist', 'youtube_url', 'source', 'source_url', 'notes']);
    }

    /**
     * @return array{name: string, original_key: ?string, capo: int, content: string}
     */
    public function versionAttributes(): array
    {
        return SongVersionRequest::toVersionAttributes($this->validated());
    }

    public function attributes(): array
    {
        return ['title' => 'título', 'source_url' => 'URL da fonte', ...SongVersionRequest::fieldAttributes()];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            ...SongVersionRequest::normalizedFields($this),
            'source' => $this->input('source', 'manual'),
        ]);
    }
}
