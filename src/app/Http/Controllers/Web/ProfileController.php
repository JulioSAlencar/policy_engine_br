<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', ['user' => $request->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $user = $request->user();
        $original = $user->only(['name']);

        $user->update($validated);

        ActivityLog::record(
            'profile.updated',
            'Atualizou o próprio perfil',
            $user,
            ['de' => $original, 'para' => $validated]
        );

        return back()->with('success', 'Perfil atualizado com sucesso.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'confirmed', PasswordRule::min(8)],
        ], [
            'current_password.current_password' => 'A senha atual está incorreta.',
        ]);

        $request->user()->update([
            'password' => Hash::make($request->password),
        ]);

        ActivityLog::record('profile.password_changed', 'Alterou a própria senha', $request->user());

        return back()->with('success', 'Senha alterada com sucesso.');
    }
}
