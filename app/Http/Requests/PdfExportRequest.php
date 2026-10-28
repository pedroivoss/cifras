<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PdfExportRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'orientation' => ['nullable', 'in:portrait,landscape'],
            'font_size' => ['nullable', 'numeric', 'between:8,18'],
            'margin' => ['nullable', 'integer', 'between:5,30'],
            'cover' => ['nullable', 'boolean'],
            'toc' => ['nullable', 'boolean'],
            'page_break' => ['nullable', 'boolean'],
            'lyrics' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'boolean'],
            'inline' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Opções com os padrões: A4 retrato, sem sumário, uma música por página; capa só quando pedida
     * (ou, sem escolha explícita, quando a playlist tem capa personalizada).
     *
     * @return array{orientation: string, font_size: float, margin: int, cover: bool, toc: bool, page_break: bool, lyrics: bool, notes: bool}
     */
    public function options(bool $coverByDefault = false): array
    {
        $has = fn (string $key, bool $default) => $this->has($key) ? $this->boolean($key) : $default;

        return [
            'orientation' => $this->input('orientation', 'portrait'),
            'font_size' => (float) $this->input('font_size', 11),
            'margin' => (int) $this->input('margin', 15),
            'cover' => $has('cover', $coverByDefault),
            'toc' => $has('toc', false),
            'page_break' => $has('page_break', true),
            'lyrics' => $has('lyrics', true),
            'notes' => $has('notes', false),
        ];
    }
}
