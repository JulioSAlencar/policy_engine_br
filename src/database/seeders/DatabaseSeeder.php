<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Cria um usuário de teste fixo para você fazer o Login
        User::factory()->create([
            'name' => 'Usuário de Teste',
            'email' => 'teste@chatbot.com',
            'password' => Hash::make('senha123'), // Senha do usuário
        ]);

        // Opcional: Se quiser que o Laravel crie mais 5 usuários com dados aleatórios
        User::factory(5)->create();
    }
}