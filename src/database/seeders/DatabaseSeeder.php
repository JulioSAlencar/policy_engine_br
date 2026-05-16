<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ─── 2 Administradores ────────────────────────────────────────────
        $admin = User::factory()->admin()->create([
            'name'     => 'Administrador GovCert',
            'email'    => 'admin@govcert.gov.br',
            'password' => Hash::make('admin123'),
        ]);

        User::factory()->admin()->create([
            'name'     => 'Auditor-Chefe',
            'email'    => 'chefe@govcert.gov.br',
            'password' => Hash::make('admin123'),
        ]);

        // ─── 5 Auditores ──────────────────────────────────────────────────
        $auditors = collect();

        $auditors->push(User::factory()->auditor()->create([
            'name'     => 'Auditor de Teste',
            'email'    => 'auditor@govcert.gov.br',
            'password' => Hash::make('auditor123'),
        ]));

        $auditors = $auditors->merge(User::factory()->auditor()->count(4)->create());

        // Pool de identificadores: auditores + um admin (todos geram logs)
        $actors = $auditors->push($admin);

        // ─── 160 logs de auditoria nos últimos 30 dias ────────────────────
        foreach (range(1, 160) as $i) {
            $actor = $actors->random();

            AuditLog::factory()->historical()->create([
                'user_id'         => $actor->id,
                'user_identifier' => $actor->email,
            ]);
        }

        $this->command->info('Seed concluído: 2 admins, 5 auditores e 160 logs de auditoria.');
        $this->command->info('Login admin:   admin@govcert.gov.br / admin123');
        $this->command->info('Login auditor: auditor@govcert.gov.br / auditor123');
    }
}
