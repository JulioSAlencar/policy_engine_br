<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::select('id', 'name', 'email', 'role', 'is_active')
            ->orderBy('name')
            ->paginate(20);

        return view('admin.users.index', compact('users'));
    }

    public function update(Request $request, User $user): RedirectResponse|JsonResponse
    {
        // Requisições JSON (AJAX) recebem 422 com os erros automaticamente;
        // requisições tradicionais recebem o redirect com erros na sessão.
        $validated = $request->validate([
            'name'  => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'role'  => ['required', Rule::in(['admin', 'auditor'])],
        ]);

        $original = $user->only(['name', 'email', 'role']);

        $user->update($validated);

        ActivityLog::record(
            'user.updated',
            "Editou o usuário {$user->email}",
            $user,
            ['de' => $original, 'para' => $validated]
        );

        if ($request->expectsJson()) {
            return response()->json([
                'message' => "Usuário {$user->name} atualizado com sucesso.",
                'user'    => [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email,
                    'role'  => $user->role,
                ],
            ]);
        }

        return redirect()
            ->route('admin.users.index')
            ->with('success', "Usuário {$user->name} atualizado com sucesso.");
    }

    public function toggleStatus(User $user): RedirectResponse
    {
        // Um admin não pode desativar a própria conta
        if ($user->id === Auth::id()) {
            return back()->with('error', 'Você não pode alterar o status da sua própria conta.');
        }

        $user->update(['is_active' => ! $user->is_active]);

        $estado = $user->is_active ? 'ativada' : 'desativada';

        ActivityLog::record(
            'user.status_changed',
            "Conta de {$user->email} {$estado}",
            $user,
            ['is_active' => $user->is_active]
        );

        return back()->with('success', "Conta de {$user->name} {$estado} com sucesso.");
    }

    public function sendResetLink(User $user): RedirectResponse
    {
        $status = Password::sendResetLink(['email' => $user->email]);

        $sent = $status === Password::RESET_LINK_SENT;

        ActivityLog::record(
            'user.reset_link_sent',
            "Enviou link de redefinição de senha para {$user->email}",
            $user,
            ['resultado' => $sent ? 'enviado' : __($status)]
        );

        return $sent
            ? back()->with('success', "Link de redefinição enviado para {$user->email}.")
            : back()->with('error', __($status));
    }
}
