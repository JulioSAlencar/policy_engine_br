<?php

namespace Database\Factories;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    public function definition(): array
    {
        return [
            'user_id'              => null,
            'user_identifier'      => fake()->userName(),
            'input_text'           => fake()->sentence(),
            'output_text'          => fake()->paragraph(),
            'url_source'           => fake()->url(),
            'captured_at'          => now(),
            'status'               => 'pending',
            'has_sensitive_data'   => null,
            'risk_level'           => null,
            'gemini_justification' => null,
            'gemini_raw_response'  => null,
            'processed_at'         => null,
        ];
    }

    public function completed(string $risk = 'high', bool $sensitive = true): static
    {
        $leak = $sensitive
            ? match ($risk) {
                'critical' => 'Credenciais',
                'high'     => 'Dados Pessoais',
                'medium'   => 'Código Fonte',
                default    => 'Dados Pessoais',
            }
            : 'Nenhum';

        return $this->state(fn () => [
            'status'               => 'completed',
            'has_sensitive_data'   => $sensitive,
            'risk_level'           => $risk,
            'leak_type'            => $leak,
            'gemini_justification' => fake()->sentence(),
            'gemini_raw_response'  => ['risk_level' => $risk, 'has_sensitive_data' => $sensitive, 'leak_type' => $leak],
            'processed_at'         => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn () => ['status' => 'failed']);
    }

    /**
     * Registro realista distribuído nos últimos 30 dias, com nível de
     * risco e status ponderados — usado para popular os gráficos.
     */
    public function historical(): static
    {
        return $this->state(function () {
            $createdAt = now()
                ->subDays(fake()->numberBetween(0, 29))
                ->subMinutes(fake()->numberBetween(0, 1440));

            // Status ponderado: maioria processada
            $status = fake()->randomElement(array_merge(
                array_fill(0, 17, 'completed'),
                array_fill(0, 2, 'pending'),
                array_fill(0, 1, 'failed'),
            ));

            $base = [
                'created_at'  => $createdAt,
                'updated_at'  => $createdAt,
                'captured_at' => $createdAt,
                'status'      => $status,
            ];

            if ($status !== 'completed') {
                return array_merge($base, [
                    'has_sensitive_data'   => null,
                    'risk_level'           => null,
                    'leak_type'            => null,
                    'gemini_justification' => null,
                    'gemini_raw_response'  => null,
                    'processed_at'         => null,
                ]);
            }

            // Distribuição de risco ponderada
            $risk = fake()->randomElement(array_merge(
                array_fill(0, 8, 'low'),
                array_fill(0, 6, 'medium'),
                array_fill(0, 4, 'high'),
                array_fill(0, 2, 'critical'),
            ));

            $sensitive = in_array($risk, ['high', 'critical'], true)
                ? true
                : fake()->boolean(20);

            $justifications = [
                'low'      => 'Nenhum dado sensível identificado na interação.',
                'medium'   => 'Possível identificação indireta do titular detectada.',
                'high'     => 'Dados pessoais diretos (nome + CPF) presentes no input.',
                'critical' => 'Dados bancários/sigilosos expostos — violação grave da LGPD.',
            ];

            $leak = $sensitive
                ? match ($risk) {
                    'critical' => 'Credenciais',
                    'high'     => 'Dados Pessoais',
                    'medium'   => 'Código Fonte',
                    default    => 'Dados Pessoais',
                }
                : 'Nenhum';

            return array_merge($base, [
                'has_sensitive_data'   => $sensitive,
                'risk_level'           => $risk,
                'leak_type'            => $leak,
                'gemini_justification' => $justifications[$risk],
                'gemini_raw_response'  => [
                    'has_sensitive_data' => $sensitive,
                    'risk_level'         => $risk,
                    'leak_type'          => $leak,
                    'justification'      => $justifications[$risk],
                ],
                'processed_at' => (clone $createdAt)->addMinutes(fake()->numberBetween(1, 30)),
            ]);
        });
    }
}
