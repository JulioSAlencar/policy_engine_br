<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitante_nao_acessa_gestao_de_tokens(): void
    {
        $this->get(route('profile.tokens'))->assertRedirect(route('login'));
    }

    public function test_tela_mostra_nenhum_token_ativo_por_padrao(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('profile.tokens'))
            ->assertOk()
            ->assertSee('Nenhum token ativo');
    }

    public function test_gerar_token_cria_um_unico_e_exibe_uma_vez(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('profile.tokens.store'));

        $response->assertRedirect(route('profile.tokens'))
                 ->assertSessionHas('plainTextToken');

        $this->assertSame(
            1,
            $user->tokens()->where('name', 'extensao-chrome')->count()
        );

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'action'  => 'token.created',
        ]);
    }

    public function test_gerar_novo_token_revoga_o_anterior(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('profile.tokens.store'));
        $firstId = $user->tokens()->where('name', 'extensao-chrome')->first()->id;

        $this->actingAs($user)->post(route('profile.tokens.store'));

        // Continua existindo apenas UM token, e não é o antigo
        $tokens = $user->tokens()->where('name', 'extensao-chrome')->get();
        $this->assertCount(1, $tokens);
        $this->assertNotEquals($firstId, $tokens->first()->id);
    }

    public function test_revogar_remove_o_token_e_registra_atividade(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('profile.tokens.store'));

        $this->actingAs($user)
            ->delete(route('profile.tokens.destroy'))
            ->assertRedirect(route('profile.tokens'))
            ->assertSessionHas('success');

        $this->assertSame(
            0,
            $user->tokens()->where('name', 'extensao-chrome')->count()
        );

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'action'  => 'token.revoked',
        ]);
    }
}
