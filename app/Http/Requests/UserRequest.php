<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

/** Cadastro/edição de contas pelo administrador. Senha opcional: sem ela, a pessoa entra só pelo Google. */
class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-users');
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'username' => Str::lower(trim((string) $this->input('username'))),
            'email' => Str::lower(trim((string) $this->input('email'))),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $user = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'min:3', 'max:40', 'regex:/^[a-z0-9._]+$/', Rule::unique('users')->ignore($user)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user)],
            'password' => ['nullable', 'string', Password::min(8)],
            'can_manage_songs' => ['boolean'],
            'playlist_limit' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'is_admin' => ['boolean'],
            'is_active' => ['boolean'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            $user = $this->route('user');

            if ($user instanceof User && $user->is($this->user()) && (! $this->boolean('is_admin') || ! $this->boolean('is_active'))) {
                $validator->errors()->add('is_admin', 'Você não pode remover o próprio acesso de administrador nem desativar a própria conta.');
            }
        }];
    }

    /**
     * @return array<string, mixed>
     */
    public function userAttributes(): array
    {
        $attributes = [
            ...$this->safe()->only(['name', 'username', 'email']),
            'can_manage_songs' => $this->boolean('can_manage_songs'),
            'playlist_limit' => $this->filled('playlist_limit') ? $this->integer('playlist_limit') : null,
            'is_admin' => $this->boolean('is_admin'),
            'is_active' => $this->boolean('is_active'),
        ];

        if ($this->filled('password')) {
            $attributes['password'] = $this->input('password');
        }

        return $attributes;
    }

    public function messages(): array
    {
        return ['username.regex' => 'Use só letras minúsculas, números, ponto ou sublinhado (sem espaços nem acentos).'];
    }

    public function attributes(): array
    {
        return ['name' => 'nome', 'username' => 'usuário', 'email' => 'e-mail', 'password' => 'senha', 'playlist_limit' => 'limite de playlists'];
    }
}
