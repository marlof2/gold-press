<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        foreach (config('permissoes') as $grupo => $dados) {
            foreach (array_keys($dados['acoes']) as $acao) {
                $permissao = $grupo.'.'.$acao;
                Gate::define($permissao, fn (User $user): bool => $user->temPermissao($permissao));
            }
        }
        if (str_starts_with((string) config('app.url'), 'https://') || request()->header('X-Forwarded-Proto') === 'https') {
            URL::forceScheme('https');
        }
    }
}
