<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(): View
    {
        return view('auth.login');
    }

    public function authenticate(Request $request): RedirectResponse
    {
        $dados = $request->validate(['email' => ['required', 'email', 'max:255'], 'password' => ['required', 'string', 'max:255']]);
        $dados['email'] = Str::lower(trim($dados['email']));
        $key = 'login:'.hash('sha256', $dados['email'].'|'.$request->ip());
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['email' => 'Muitas tentativas. Aguarde '.RateLimiter::availableIn($key).' segundos.']);
        }
        if (! Auth::attempt($dados, $request->boolean('remember'))) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['email' => 'E-mail ou senha incorretos.']);
        }
        RateLimiter::clear($key);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function account(): View
    {
        return view('auth.account');
    }

    public function password(Request $request): RedirectResponse
    {
        $dados = $request->validate(['current_password' => ['required', 'current_password'], 'password' => ['required', 'confirmed', Password::min(12)->letters()->numbers()]]);
        $request->user()->update(['password' => $dados['password'], 'remember_token' => Str::random(60)]);
        $request->session()->regenerate();

        return back()->with('success', 'Senha atualizada com sucesso.');
    }
}
