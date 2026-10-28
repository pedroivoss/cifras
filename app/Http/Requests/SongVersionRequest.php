<?php

namespace App\Http\Requests;

use App\Services\Chords\Key;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Campos de uma versão (nome, tom escrito, capotraste e cifra). As regras também são usadas
 * pelo formulário completo da música (SongRequest).
 */
class SongVersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('song'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return self::fieldRules();
    }

    /**
     * @return array<string, mixed>
     */
    public static function fieldRules(): array
    {
        return [
            'version_name' => ['nullable', 'string', 'max:60'],
            'original_key' => ['nullable', 'string', function (string $attribute, mixed $value, Closure $fail) {
                if (! Key::isValid($value)) {
                    $fail('Tom inválido. Use, por exemplo, C, F#, Bb, Am ou C#m.');
                }
            }],
            'capo' => ['nullable', 'integer', 'between:0,11'],
            'content' => ['required', 'string', 'max:100000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function fieldAttributes(): array
    {
        return ['version_name' => 'nome da versão', 'content' => 'cifra', 'original_key' => 'tom', 'capo' => 'capotraste'];
    }

    /**
     * Normaliza quebras de linha do Windows sem tocar em espaços (alinhamento da cifra).
     *
     * @return array<string, mixed>
     */
    public static function normalizedFields(FormRequest $request): array
    {
        return [
            'content' => str_replace("\r\n", "\n", (string) $request->input('content')),
            'original_key' => $request->filled('original_key') ? trim($request->input('original_key')) : null,
            'capo' => (int) $request->input('capo', 0),
        ];
    }

    /**
     * Atributos prontos para SongVersion.
     *
     * @return array{name: string, original_key: ?string, capo: int, content: string}
     */
    public function versionAttributes(): array
    {
        return self::toVersionAttributes($this->validated());
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{name: string, original_key: ?string, capo: int, content: string}
     */
    public static function toVersionAttributes(array $validated): array
    {
        return [
            'name' => trim((string) ($validated['version_name'] ?? '')) ?: 'Principal',
            'original_key' => $validated['original_key'] ?? null,
            'capo' => (int) ($validated['capo'] ?? 0),
            'content' => $validated['content'],
        ];
    }

    public function attributes(): array
    {
        return self::fieldAttributes();
    }

    protected function prepareForValidation(): void
    {
        $this->merge(self::normalizedFields($this));
    }
}
