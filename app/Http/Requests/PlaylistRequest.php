<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PlaylistRequest extends FormRequest
{
    public function authorize(): bool
    {
        $playlist = $this->route('playlist');

        return $playlist === null || $this->user()->can('update', $playlist);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'event_date' => ['nullable', 'date'],
            'cover_title' => ['nullable', 'string', 'max:255'],
            'cover_quote' => ['nullable', 'string', 'max:1000'],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:5120', 'dimensions:max_width=8000,max_height=8000'],
            'remove_cover_image' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Campos salvos direto no model (a imagem é tratada à parte).
     *
     * @return array<string, mixed>
     */
    public function playlistAttributes(): array
    {
        return $this->safe()->only(['name', 'description', 'event_date', 'cover_title', 'cover_quote']);
    }

    public function attributes(): array
    {
        return [
            'name' => 'nome',
            'event_date' => 'data do evento',
            'cover_title' => 'título da capa',
            'cover_quote' => 'passagem',
            'cover_image' => 'imagem da capa',
        ];
    }
}
