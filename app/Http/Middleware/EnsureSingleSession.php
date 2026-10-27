<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureSingleSession
{
    /**
     * Encerra a sessão anterior quando a mesma conta entra em outro dispositivo.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();
        $token = $request->session()->get('single_session');

        if ($usuario && $token && ! hash_equals((string) $usuario->session_token, (string) $token)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['email' => 'Sua sessão foi encerrada porque a conta entrou em outro dispositivo ou teve a senha alterada. Entre novamente.']);
        }

        return $next($request);
    }
}
