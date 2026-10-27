<?php

namespace App\Http\Requests;

use App\Models\Song;
use App\Services\Chords\Key;
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
        return [
            'title' => ['required', 'string', 'max:255'],
            'artist' => ['nullable', 'string', 'max:255'],
            'original_key' => ['nullable', 'string', function (string $attribute, mixed $value, Closure $fail) {
                if (! Key::isValid($value)) {
                    $fail('Tom inválido. Use, por exemplo, C, F#, Bb, Am ou C#m.');
                }
            }],
            'content' => ['required', 'string', 'max:100000'],
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

    public function attributes(): array
    {
        return ['title' => 'título', 'content' => 'cifra', 'original_key' => 'tom original', 'source_url' => 'URL da fonte'];
    }

    /** Remove o "\r" de quebras Windows sem tocar em espaços (alinhamento da cifra). */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'content' => str_replace("\r\n", "\n", (string) $this->input('content')),
            'original_key' => $this->filled('original_key') ? trim($this->input('original_key')) : null,
            'source' => $this->input('source', 'manual'),
        ]);
    }
}
