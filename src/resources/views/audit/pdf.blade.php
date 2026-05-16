<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Auditoria de IA</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 9px; color: #1f2937; background: #fff; }

        .header { background: #071D41; color: #fff; padding: 16px 20px; margin-bottom: 16px; border-bottom: 4px solid #FFCD07; }
        .header h1 { font-size: 16px; font-weight: bold; }
        .header p { font-size: 9px; color: #adcdee; margin-top: 2px; }

        .meta { display: flex; gap: 20px; margin: 0 20px 16px; font-size: 8px; color: #6b7280; }

        table { width: 100%; border-collapse: collapse; margin: 0 0 16px; }
        thead { background: #d4e5f7; }
        th { padding: 6px 8px; text-align: left; font-size: 7.5px; font-weight: bold; color: #0C326F; border-bottom: 2px solid #1351B4; }
        td { padding: 5px 8px; border-bottom: 1px solid #e5e7eb; vertical-align: top; font-size: 8px; }
        tr:nth-child(even) td { background: #f9fafb; }

        .badge { display: inline-block; padding: 1px 5px; border-radius: 3px; font-size: 7px; font-weight: bold; }
        .badge-low      { background: #dcfce7; color: #166534; }
        .badge-medium   { background: #fef9c3; color: #854d0e; }
        .badge-high     { background: #ffedd5; color: #9a3412; }
        .badge-critical { background: #fee2e2; color: #991b1b; }
        .badge-pending  { background: #fef9c3; color: #854d0e; }
        .badge-completed{ background: #dcfce7; color: #166534; }
        .badge-failed   { background: #fee2e2; color: #991b1b; }
        .badge-yes      { background: #fee2e2; color: #991b1b; }
        .badge-no       { background: #dcfce7; color: #166534; }

        .footer { margin: 8px 20px 0; font-size: 7px; color: #9ca3af; text-align: center; border-top: 1px solid #e5e7eb; padding-top: 6px; }

        .section-title { margin: 0 0 8px; font-size: 11px; font-weight: bold; color: #1351B4; padding: 0 20px; }
    </style>
</head>
<body>

<div class="header">
    <h1>Relatório de Auditoria de IA — Governança Pública</h1>
    <p>GovCert &mdash; Gerado em {{ now()->format('d/m/Y \à\s H:i:s') }}</p>
</div>

<div class="meta">
    <span>Total de registros: <strong>{{ $logs->count() }}</strong></span>
    <span>Com dados sensíveis: <strong>{{ $logs->where('has_sensitive_data', true)->count() }}</strong></span>
    <span>Risco crítico: <strong>{{ $logs->where('risk_level', 'critical')->count() }}</strong></span>
</div>

<p class="section-title">Registros de Auditoria</p>

<table>
    <thead>
        <tr>
            <th style="width:4%">ID</th>
            <th style="width:14%">Usuário</th>
            <th style="width:18%">URL Origem</th>
            <th style="width:7%">Sensível</th>
            <th style="width:8%">Risco</th>
            <th style="width:8%">Status</th>
            <th style="width:11%">Capturado em</th>
            <th style="width:30%">Justificativa Gemini</th>
        </tr>
    </thead>
    <tbody>
        @foreach($logs as $log)
        <tr>
            <td>#{{ $log->id }}</td>
            <td>{{ $log->user_identifier }}</td>
            <td style="word-break:break-all; font-size:7px;">{{ parse_url($log->url_source, PHP_URL_HOST) }}</td>
            <td>
                @if($log->has_sensitive_data === null)
                    —
                @elseif($log->has_sensitive_data)
                    <span class="badge badge-yes">Sim</span>
                @else
                    <span class="badge badge-no">Não</span>
                @endif
            </td>
            <td>
                @if($log->risk_level)
                    <span class="badge badge-{{ $log->risk_level }}">
                        {{ ['low'=>'Baixo','medium'=>'Médio','high'=>'Alto','critical'=>'Crítico'][$log->risk_level] ?? $log->risk_level }}
                    </span>
                @else —
                @endif
            </td>
            <td>
                <span class="badge badge-{{ $log->status }}">
                    {{ ['pending'=>'Pendente','processing'=>'Processando','completed'=>'Concluído','failed'=>'Falhou'][$log->status] ?? $log->status }}
                </span>
            </td>
            <td>{{ $log->captured_at?->format('d/m/Y H:i') }}</td>
            <td style="font-size:7.5px;">{{ \Illuminate\Support\Str::limit($log->gemini_justification ?? '—', 200) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="footer">
    Este relatório é de uso restrito. Gerado automaticamente pelo sistema GovCert de Auditoria e Governança de IA.
</div>

</body>
</html>
