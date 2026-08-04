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
        @if(!empty($periodLabel))
            <div class="header-period">Período de referência: <strong>{{ $periodLabel }}</strong></div>
        @endif
    </div>

    <table class="data-table">
        <thead>
            <tr>
                @foreach ($columns as $column)
                    <th>{{ $column['label'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    @foreach ($columns as $column)
                        <td>{{ is_object($row) ? ($row->{$column['key']} ?? '—') : ($row[$column['key']] ?? '—') }}</td>
                    @endforeach
                </tr>
            @empty
                <tr class="empty-row">
                    <td colspan="{{ count($columns) }}">Nenhum registro encontrado.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

</body>
</html>