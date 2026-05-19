<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ExtensionAuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email'       => 'required|email',
            'password'    => 'required|string',
            'device_name' => 'required|string|max:255',
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['As credenciais fornecidas estão incorretas.'],
            ]);
        }

        if (! $user->is_active) {
            return response()->json([
                'message' => 'Conta desativada. Entre em contato com o administrador.',
            ], 403);
        }

        // Revoga tokens anteriores do mesmo dispositivo para evitar acúmulo
        $user->tokens()->where('name', $data['device_name'])->delete();

        $token = $user->createToken($data['device_name'])->plainTextToken;

        return response()->json([
            'token'      => $token,
            'user_name'  => $user->name,
            'user_email' => $user->email,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Desconectado com sucesso.']);
    }
}
