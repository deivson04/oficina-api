<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ForgotPasswordController extends Controller
{
    // 1. Pedido de recuperação (Gera o Token)
    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        // Gera uma string aleatória para servir de token
        $token = Str::random(60);

        // Guarda ou atualiza o token na tabela padrão do Laravel
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            [
                'token' => Hash::make($token),
                'created_at' => now(),
            ]
        );

        // Retorna o token na resposta JSON para facilitar o teste no React/Postman
        return response()->json([
            'message' => 'Token de recuperação gerado com sucesso.',
            'reset_token' => $token, // Em produção enviaria por email, no dev usamos direto no front!
            'email' => $request->email
        ], 200);
    }

    // 2. Redefinição efetiva da Senha
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed', // Espera password_confirmation no request
        ]);

        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        // Valida se o token existe e se bate com o hash guardado
        if (!$record || !Hash::check($request->token, $record->token)) {
            return response()->json(['message' => 'Token de recuperação inválido ou expirado.'], 400);
        }

        // Atualiza a senha do utilizador
        $user = User::where('email', $request->email)->first();
        $user->password = Hash::make($request->password);
        $user->save();

        // Elimina o token usado para não ser reutilizado
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json(['message' => 'Senha alterada com sucesso!'], 200);
    }
}
