<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(): View
    {
        return view('auth.login');
    }

    public function authenticate(Request $request): RedirectResponse
    {
        $dados = $request->validate(['email' => ['required', 'email', 'max:255'], 'password' => ['required', 'string']]);
        $dados['email'] = Str::lower(trim($dados['email']));
        $key = 'login:'.hash('sha256', $dados['email'].'|'.$request->ip());
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['email' => 'Muitas tentativas. Aguarde '.RateLimiter::availableIn($key).' segundos.']);
        }
        if (! Auth::attempt($dados)) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['email' => 'E-mail ou senha incorretos.']);
        }
        RateLimiter::clear($key);
        $request->session()->regenerate();
        $token = Str::random(64);
        $request->session()->put('single_session', $token);
        $request->user()->forceFill([
            'session_token' => $token,
            'last_login_at' => now(),
        ])->save();

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->user()?->forceFill(['session_token' => null])->save();
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
        $dados = $request->validate(['current_password' => ['required', 'current_password'], 'password' => ['required', 'string', 'confirmed']]);
        $request->user()->update(['password' => $dados['password']]);
        $request->session()->regenerate();
        $token = Str::random(64);
        $request->session()->put('single_session', $token);
        $request->user()->forceFill(['session_token' => $token])->save();

        return back()->with('success', 'Senha atualizada com sucesso.');
    }
}
