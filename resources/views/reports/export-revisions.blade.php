<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <style>
        @page {
            margin: 25px 30px;
        }

        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 10px;
            color: #334155;
            background-color: #ffffff;
            margin: 0;
            padding: 0;
        }

        /* --- Header Estilizado --- */
        .header-container {
            border-bottom: 2px solid #0f172a;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }

        .header-title {
            font-size: 20px;
            font-weight: bold;
            color: #0f172a;
            margin: 0 0 4px 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .header-part {
            font-size: 12px;
            color: #2563eb;
            font-weight: bold;
            text-transform: none;
        }

        .header-period {
            font-size: 11px;
            color: #64748b;
            margin-top: 2px;
        }

        /* --- Seções e Títulos --- */
        .section-title {
            font-size: 12px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 22px;
            margin-bottom: 10px;
            padding-bottom: 4px;
            border-bottom: 1px solid #cbd5e1;
        }

        /* --- Tabelas Estilizadas --- */
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
            page-break-inside: avoid;
        }

        table.data-table th {
            background-color: #0f172a;
            color: #ffffff;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 7px 10px;
            text-align: left;
            border: none;
        }

        table.data-table td {
            padding: 6px 10px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 10px;
            color: #334155;
        }

        table.data-table tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }

        /* --- Badges --- */
        .badge {
            display: inline-block;
            padding: 2px 6px;
            font-size: 9px;
            font-weight: bold;
            border-radius: 3px;
            background-color: #e2e8f0;
            color: #334155;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }

        .empty-row td {
            text-align: center;
            color: #94a3b8;
            font-style: italic;
        }
    </style>
</head>
<body>

    <div class="header-container">
        <div class="header-title">
            {{ $title }}
            @if(!empty($partInfo))
                <span class="header-part">({{ $partInfo }})</span>
            @endif
        </div>
        <div class="header-period">Período de referência: <strong>{{ $periodLabel }}</strong></div>
    </div>

    <div class="section-title">Tempo Médio Entre Revisões (Por Pessoa)</div>
    <table class="data-table">
        <thead>
            <tr>
                <th width="70%">Pessoa</th>
                <th width="30%" class="text-right">Média (Dias)</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($avgIntervalRows as $row)
                <tr>
                    <td class="font-bold">{{ $row['person_name'] }}</td>
                    <td class="text-right"><span class="badge">{{ isset($row['avg_days']) ? round(abs($row['avg_days'])) . ' dias' : '—' }}</span></td>
                </tr>
            @empty
                <tr class="empty-row">
                    <td colspan="2">Nenhum registro encontrado.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">Revisões no Período Selecionado</div>
    <table class="data-table">
        <thead>
            <tr>
                <th width="15%">Data</th>
                <th width="25%">Pessoa</th>
                <th width="25%">Veículo</th>
                <th width="35%">Descrição</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($periodRows as $row)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($row->date)->format('d/m/Y') }}</td>
                    <td>{{ $row->person_name }}</td>
                    <td>{{ $row->vehicle }}</td>
                    <td>{{ $row->description }}</td>
                </tr>
            @empty
                <tr class="empty-row">
                    <td colspan="4">Nenhuma revisão encontrada nesse período.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

</body>
</html>