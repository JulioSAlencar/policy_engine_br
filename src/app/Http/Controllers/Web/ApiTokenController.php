<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApiTokenController extends Controller
{
    /**
     * Nome fixo do token da extensão. Todo o controle de "token único"
     * é feito por este nome — o usuário tem, no máximo, UM token ativo.
     */
    private const TOKEN_NAME = 'extensao-chrome';

    public function show(Request $request): View
    {
        $token = $request->user()->tokens()
            ->where('name', self::TOKEN_NAME)
            ->latest()
            ->first();

        return view('profile.api-tokens', ['token' => $token]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        // Regra de negócio: apenas UM token ativo para a extensão.
        // Gerar um novo SEMPRE revoga o anterior.
        $user->tokens()->where('name', self::TOKEN_NAME)->delete();

        $new = $user->createToken(self::TOKEN_NAME);

        ActivityLog::record(
            'token.created',
            'Gerou um novo API Token da extensão (token anterior revogado)',
            $user
        );

        // plainTextToken vai por flash one-time: exibido só nesta resposta.
        return redirect()
            ->route('profile.tokens')
            ->with('plainTextToken', $new->plainTextToken);
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        $revoked = $user->tokens()->where('name', self::TOKEN_NAME)->delete();

        if ($revoked > 0) {
            ActivityLog::record(
                'token.revoked',
                'Revogou o API Token da extensão',
                $user
            );

            return redirect()
                ->route('profile.tokens')
                ->with('success', 'Acesso revogado. A extensão deixará de enviar dados até que um novo token seja gerado.');
        }

        return redirect()
            ->route('profile.tokens')
            ->with('error', 'Não havia token ativo para revogar.');
    }
}
