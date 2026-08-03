<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #1f2937; }
        h1 { font-size: 18px; margin-bottom: 4px; }
        h2 { font-size: 14px; margin-top: 20px; margin-bottom: 8px; }
        .subtitle { font-size: 11px; color: #6b7280; margin-bottom: 16px; }
        .part-info { font-size: 10px; color: #9ca3af; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { padding: 6px 8px; border-bottom: 1px solid #e5e7eb; text-align: left; font-size: 11px; }
        th { background: #f3f4f6; font-weight: 600; }
        tr:nth-child(even) { background: #fafafa; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <div class="subtitle">Período: {{ $periodLabel }}</div>

    @if(!empty($partInfo))
        <div class="part-info">{{ $partInfo }}</div>
    @endif

    <h2>Tempo médio entre revisões (por pessoa)</h2>
    <table>
        <thead>
            <tr>
                <th>Pessoa</th>
                <th>Média (dias)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($avgIntervalRows as $row)
                <tr>
                    <td>{{ $row['person_name'] }}</td>
                    <td>{{ $row['avg_days'] ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="2">Nenhum registro encontrado.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Revisões no período selecionado</h2>
    <table>
        <thead>
            <tr>
                <th>Data</th>
                <th>Pessoa</th>
                <th>Veículo</th>
                <th>Descrição</th>
            </tr>
        </thead>
        <tbody>
            @forelse($periodRows as $row)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($row->date)->format('d/m/Y') }}</td>
                    <td>{{ $row->person_name }}</td>
                    <td>{{ $row->vehicle }}</td>
                    <td>{{ $row->description }}</td>
                </tr>
            @empty
                <tr><td colspan="4">Nenhuma revisão encontrada nesse período.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>