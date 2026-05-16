<?php

namespace Database\Factories;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    /**
     * Cenários de vazamento hiper-realistas em PT-BR.
     * Usados para popular a interface de leitura (chat) com conteúdo
     * crível — nunca Lorem Ipsum.
     */
    public static function scenarios(): array
    {
        return [
            // 1 — Credenciais AWS coladas em código
            [
                'input_text'  => "Pode revisar meu script de deploy? Não está subindo o relatório pro S3:\n\n```python\nimport boto3\nAWS_ACCESS_KEY_ID = 'AKIA5T9QZ8XK2J4LMNOP'\nAWS_SECRET_ACCESS_KEY = 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY'\ns3 = boto3.client('s3', aws_access_key_id=AWS_ACCESS_KEY_ID, aws_secret_access_key=AWS_SECRET_ACCESS_KEY)\ns3.upload_file('relatorio_orcamentario.pdf', 'gov-bucket-prod', 'relatorio.pdf')\n```",
                'output_text' => "Encontrei o problema: faltou definir a região do bucket. Adicione `region_name='sa-east-1'` na criação do client. Importante: nunca deixe as chaves AWS fixas no código — mova para variáveis de ambiente ou use IAM Roles. Segue a versão corrigida...",
                'url_source'  => 'https://chatgpt.com/c/8f2a-deploy-s3',
                'leak_type'   => 'Credenciais AWS',
                'risk_level'  => 'critical',
                'has_sensitive' => true,
                'justification' => 'Exposição de Access Key ID e Secret Access Key da AWS em texto claro, coladas em ferramenta de IA pública. As credenciais dão acesso a bucket de produção governamental. Risco crítico de acesso não autorizado — recomenda-se rotação imediata das chaves e auditoria do CloudTrail.',
            ],

            // 2 — Prontuário médico
            [
                'input_text'  => "Resume esse prontuário em linguagem simples para o paciente entender:\n\nPaciente: Maria Aparecida de Souza, 58 anos, CPF 287.654.123-09. Diagnóstico: Diabetes Mellitus tipo 2 descompensada, hipertensão arterial estágio 2. HbA1c 9,4%. Em uso de metformina 850mg e losartana 50mg. Histórico de internação por cetoacidose em 03/2024.",
                'output_text' => "Claro. Em resumo: a paciente tem diabetes que está fora de controle e pressão alta. O exame de HbA1c indica que a glicose ficou elevada nos últimos três meses. O tratamento atual envolve dois medicamentos. É importante manter acompanhamento regular...",
                'url_source'  => 'https://claude.ai/chat/prontuario-resumo',
                'leak_type'   => 'Dados Médicos Pessoais',
                'risk_level'  => 'critical',
                'has_sensitive' => true,
                'justification' => 'Prontuário com nome civil, CPF, diagnósticos, medicações e histórico de internação submetido a IA de terceiros. Dados de saúde são categoria especial (art. 11 da LGPD) e exigem base legal específica. Tratamento irregular de dado sensível de paciente.',
            ],

            // 3 — Lista de CPFs para formatar
            [
                'input_text'  => "Transforma essa lista de clientes em CSV com as colunas nome;cpf;email:\nJoão Carlos Pereira 111.222.333-44 joao.pereira@gmail.com\nFernanda Lima Rocha 555.666.777-88 fernanda.rocha@hotmail.com\nRoberto Alves Nunes 999.888.777-66 roberto.nunes@yahoo.com.br",
                'output_text' => "Segue o conteúdo em CSV:\n\nnome;cpf;email\nJoão Carlos Pereira;111.222.333-44;joao.pereira@gmail.com\nFernanda Lima Rocha;555.666.777-88;fernanda.rocha@hotmail.com\nRoberto Alves Nunes;999.888.777-66;roberto.nunes@yahoo.com.br",
                'url_source'  => 'https://gemini.google.com/app/csv-clientes',
                'leak_type'   => 'PII',
                'risk_level'  => 'high',
                'has_sensitive' => true,
                'justification' => 'Base com nome completo, CPF e e-mail de múltiplos titulares enviada a IA externa para formatação. Compartilhamento de dados pessoais com operador não contratado e sem base legal — afronta os arts. 6º (necessidade) e 7º da LGPD.',
            ],

            // 4 — Código-fonte com string de conexão de produção
            [
                'input_text'  => "Otimiza essa query, está lenta em produção:\n\n```php\n\$pdo = new PDO('mysql:host=10.12.0.44;dbname=siafi_prod', 'svc_integra', 'P@ssGov#2024!');\n\$rows = \$pdo->query(\"SELECT * FROM empenhos e JOIN fornecedores f ON f.id = e.fornecedor_id\")->fetchAll();\n```",
                'output_text' => "A query faz full scan e usa SELECT *. Crie um índice composto em `empenhos(data, fornecedor_id)` e selecione só as colunas necessárias. Recomendo também não versionar a string de conexão com a senha em texto claro...",
                'url_source'  => 'https://chatgpt.com/c/otimizacao-query-siafi',
                'leak_type'   => 'Código Fonte Proprietário',
                'risk_level'  => 'high',
                'has_sensitive' => true,
                'justification' => 'Trecho de código interno expondo string de conexão de banco de produção (host interno, usuário de serviço e senha) de sistema financeiro. Vazamento de segredo de infraestrutura e propriedade intelectual — exige rotação de credenciais e revisão da política de uso de IA.',
            ],

            // 5 — Dados de cartão de crédito corporativo
            [
                'input_text'  => "Me ajuda a preencher o formulário de reembolso? Cartão corporativo número 5234 9912 3344 4471, validade 08/27, CVV 318, titular GOVCERT TECNOLOGIA LTDA, valor da fatura R\$ 12.430,00.",
                'output_text' => "Posso estruturar o pedido de reembolso com os campos necessários. Antes disso, um alerta de segurança: evite compartilhar o número completo do cartão e o CVV em qualquer ferramenta — esses dados bastam para transações fraudulentas...",
                'url_source'  => 'https://claude.ai/chat/reembolso-cartao',
                'leak_type'   => 'Dados Financeiros',
                'risk_level'  => 'critical',
                'has_sensitive' => true,
                'justification' => 'Número completo de cartão de crédito corporativo, validade e CVV inseridos em IA pública — dados suficientes para fraude transacional imediata. Risco crítico: necessário bloqueio preventivo do cartão e comunicação ao setor financeiro.',
            ],

            // 6 — Pergunta técnica genérica (sem vazamento)
            [
                'input_text'  => "Qual a diferença entre debounce e throttle em JavaScript? Pode dar um exemplo de debounce?",
                'output_text' => "Debounce adia a execução até o usuário parar de disparar o evento; throttle limita a frequência. Exemplo de debounce:\n\n```js\nfunction debounce(fn, delay) {\n  let t;\n  return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), delay); };\n}\n```",
                'url_source'  => 'https://gemini.google.com/app/duvida-js',
                'leak_type'   => 'Nenhum',
                'risk_level'  => 'low',
                'has_sensitive' => false,
                'justification' => 'Interação puramente técnica sobre conceitos de programação. Nenhum dado pessoal, credencial ou informação sigilosa identificado. Uso adequado da ferramenta de IA.',
            ],
        ];
    }

    public function definition(): array
    {
        $scenario = Arr::random(self::scenarios());

        return [
            'user_id'              => null,
            'user_identifier'      => fake()->userName(),
            'input_text'           => $scenario['input_text'],
            'output_text'          => $scenario['output_text'],
            'url_source'           => $scenario['url_source'],
            'captured_at'          => now(),
            'status'               => 'pending',
            'has_sensitive_data'   => null,
            'risk_level'           => null,
            'leak_type'            => null,
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
            'gemini_justification' => 'Parecer técnico do auditor para o cenário de risco ' . $risk . '.',
            'gemini_raw_response'  => ['risk_level' => $risk, 'has_sensitive_data' => $sensitive, 'leak_type' => $leak],
            'processed_at'         => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn () => ['status' => 'failed']);
    }

    /**
     * Sorteia um cenário realista e o materializa como log concluído,
     * com timestamp aleatório nos últimos 60 dias — alimenta os gráficos
     * e a interface de leitura.
     */
    public function scenario(): static
    {
        return $this->state(function () {
            $s = Arr::random(self::scenarios());

            /** @var Carbon $createdAt */
            $createdAt = now()
                ->subDays(fake()->numberBetween(0, 59))
                ->subMinutes(fake()->numberBetween(0, 1439));

            return [
                'input_text'           => $s['input_text'],
                'output_text'          => $s['output_text'],
                'url_source'           => $s['url_source'],
                'leak_type'            => $s['leak_type'],
                'risk_level'           => $s['risk_level'],
                'has_sensitive_data'   => $s['has_sensitive'],
                'status'               => 'completed',
                'gemini_justification' => $s['justification'],
                'gemini_raw_response'  => [
                    'has_sensitive_data' => $s['has_sensitive'],
                    'risk_level'         => $s['risk_level'],
                    'leak_type'          => $s['leak_type'],
                    'justification'      => $s['justification'],
                ],
                'captured_at'          => $createdAt,
                'created_at'           => $createdAt,
                'updated_at'           => $createdAt,
                'processed_at'         => (clone $createdAt)->addMinutes(fake()->numberBetween(1, 25)),
            ];
        });
    }
}
