<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

#[Signature('cifra:create-admin {--email= : E-mail do administrador} {--name= : Nome exibido}')]
#[Description('Cria (ou redefine a senha de) um administrador. A senha é sempre digitada de forma interativa.')]
class CreateAdmin extends Command
{
    public function handle(): int
    {
        $email = $this->option('email') ?: $this->ask('E-mail');
        $existing = User::where('email', $email)->first();

        if ($existing && ! $this->confirm("Já existe um usuário com {$email}. Redefinir a senha?")) {
            return self::FAILURE;
        }

        $name = $existing?->name ?? ($this->option('name') ?: $this->ask('Nome', 'Administrador'));
        $password = $this->secret('Senha (mín. 10 caracteres)');
        $confirmation = $this->secret('Confirme a senha');

        $validator = Validator::make(
            ['name' => $name, 'email' => $email, 'password' => $password, 'password_confirmation' => $confirmation],
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255'],
                'password' => ['required', 'confirmed', Password::min(10)],
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = $existing ?? new User(['email' => $email]);
        $user->fill(['name' => $name, 'password' => $password])->save();
        $user->seedDefaultTags();

        $this->info($existing ? 'Senha redefinida.' : "Administrador {$email} criado com as tags iniciais.");

        return self::SUCCESS;
    }
}
