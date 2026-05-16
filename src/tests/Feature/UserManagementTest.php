<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_pode_editar_nome_e_email_do_usuario(): void
    {
        $admin  = User::factory()->admin()->create();
        $target = User::factory()->auditor()->create(['name' => 'Antigo', 'email' => 'antigo@govcert.gov.br']);

        $response = $this->actingAs($admin)->put(route('admin.users.update', $target), [
            'name'  => 'Nome Novo',
            'email' => 'novo@govcert.gov.br',
            'role'  => 'auditor',
        ]);

        $response->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'id'    => $target->id,
            'name'  => 'Nome Novo',
            'email' => 'novo@govcert.gov.br',
        ]);
    }

    public function test_edicao_de_usuario_gera_registro_de_atividade(): void
    {
        $admin  = User::factory()->admin()->create();
        $target = User::factory()->auditor()->create();

        $this->actingAs($admin)->put(route('admin.users.update', $target), [
            'name'  => 'Editado',
            'email' => $target->email,
            'role'  => 'auditor',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'user_id'      => $admin->id,
            'action'       => 'user.updated',
            'subject_type' => User::class,
            'subject_id'   => $target->id,
        ]);
    }

    public function test_email_duplicado_e_rejeitado_na_edicao(): void
    {
        $admin = User::factory()->admin()->create();
        $a     = User::factory()->create(['email' => 'a@govcert.gov.br']);
        User::factory()->create(['email' => 'b@govcert.gov.br']);

        $response = $this->actingAs($admin)->put(route('admin.users.update', $a), [
            'name'  => $a->name,
            'email' => 'b@govcert.gov.br',
            'role'  => 'auditor',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_auditor_nao_acessa_gestao_de_usuarios(): void
    {
        $auditor = User::factory()->auditor()->create();

        $this->actingAs($auditor)
            ->get(route('admin.users.index'))
            ->assertStatus(403);
    }

    public function test_admin_atualiza_usuario_via_ajax_recebe_json(): void
    {
        $admin  = User::factory()->admin()->create();
        $target = User::factory()->auditor()->create();

        $response = $this->actingAs($admin)->putJson(route('admin.users.update', $target), [
            'name'  => 'Via AJAX',
            'email' => 'ajax@govcert.gov.br',
            'role'  => 'admin',
        ]);

        $response->assertOk()
                 ->assertJson([
                     'user' => [
                         'id'    => $target->id,
                         'name'  => 'Via AJAX',
                         'email' => 'ajax@govcert.gov.br',
                         'role'  => 'admin',
                     ],
                 ]);

        $this->assertDatabaseHas('users', [
            'id'   => $target->id,
            'name' => 'Via AJAX',
            'role' => 'admin',
        ]);
    }

    public function test_update_via_ajax_com_email_duplicado_retorna_422(): void
    {
        $admin = User::factory()->admin()->create();
        $a     = User::factory()->create(['email' => 'ax@govcert.gov.br']);
        User::factory()->create(['email' => 'bx@govcert.gov.br']);

        $response = $this->actingAs($admin)->putJson(route('admin.users.update', $a), [
            'name'  => $a->name,
            'email' => 'bx@govcert.gov.br',
            'role'  => 'auditor',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors('email');
    }

    public function test_login_web_gera_registro_de_atividade(): void
    {
        $user = User::factory()->create([
            'email'    => 'login@govcert.gov.br',
            'password' => bcrypt('senha123'),
        ]);

        $this->post('/login', [
            'email'    => 'login@govcert.gov.br',
            'password' => 'senha123',
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'action'  => 'auth.login',
        ]);
    }

    public function test_usuario_desativado_e_deslogado_e_atividade_registrada(): void
    {
        $user = User::factory()->inactive()->create();

        $this->actingAs($user)->get('/')->assertRedirect(route('login'));

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'action'  => 'auth.blocked',
        ]);
    }
}
