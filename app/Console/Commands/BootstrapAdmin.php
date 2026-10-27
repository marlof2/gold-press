<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class BootstrapAdmin extends Command
{
    protected $signature = 'app:bootstrap-admin';

    protected $description = 'Cria o primeiro administrador a partir das variáveis ADMIN_*, sem alterar contas existentes.';

    public function handle(): int
    {
        if (User::exists()) {
            return self::SUCCESS;
        }
        $dados = ['name' => config('grafica.admin_name'), 'email' => config('grafica.admin_email'), 'password' => config('grafica.admin_password')];
        $validator = Validator::make($dados, ['name' => ['required', 'string'], 'email' => ['required', 'email'], 'password' => ['required', Password::min(12)->letters()->numbers()]]);
        if ($validator->fails()) {
            $this->error('Configure ADMIN_NAME, ADMIN_EMAIL e ADMIN_PASSWORD (mínimo 12 caracteres, letras e números) para criar o acesso inicial.');

            return self::FAILURE;
        }
        User::create($dados);
        $this->info('Acesso inicial criado. Altere a senha em Minha conta.');

        return self::SUCCESS;
    }
}
