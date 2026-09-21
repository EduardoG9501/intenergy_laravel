<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->intended('/inicio');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)
                    ->where('estado', 1)
                    ->first();

        if (!$user) {
            return back()->withErrors([
                'email' => 'El correo electrónico no coincide con nuestros registros o está inactivo.',
            ])->withInput($request->only('email'));
        }

        $passwordValido = false;

        // Caso 1: La contraseña está almacenada en bcrypt (comienza con $2y$ o $2a$, etc. - estándar de Laravel)
        if (str_starts_with($user->password, '$2y$') || str_starts_with($user->password, '$2a$')) {
            if (Hash::check($request->password, $user->password)) {
                $passwordValido = true;
            }
        } else {
            // Caso 2: La contraseña está en SHA-1 (sistema original)
            if ($user->password === sha1($request->password)) {
                $passwordValido = true;

                // Transición automática: Guardar el nuevo hash en Bcrypt
                $user->password = Hash::make($request->password);
                $user->save();
            }
        }

        if ($passwordValido) {
            Auth::login($user);
            $request->session()->regenerate();

            // Guardar iduser en sesión para compatibilidad
            session(['iduser' => $user->id_usuario]);

            return redirect()->intended('/inicio');
        }

        return back()->withErrors([
            'password' => 'La contraseña proporcionada es incorrecta.',
        ])->withInput($request->only('email'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }
}
