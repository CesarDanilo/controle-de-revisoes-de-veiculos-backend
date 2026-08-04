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

        /* --- KPIs (Cards) --- */
        .kpi-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 6px 6px;
            margin-bottom: 4px;
            margin-left: -6px;
            margin-right: -6px;
        }

        .kpi-card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-left: 4px solid #2563eb;
            border-radius: 4px;
            padding: 10px 12px;
        }

        .kpi-card.accent-green {
            border-left-color: #10b981;
        }

        .kpi-label {
            font-size: 8px;
            font-weight: bold;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .kpi-value {
            font-size: 15px;
            font-weight: bold;
            color: #0f172a;
            margin-top: 4px;
        }

        .kpi-value.accent-green-text {
            color: #047857;
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

        /* --- Gráficos em Barras (CSS Pure Bar Charts) --- */
        .bar-container {
            width: 100%;
            background-color: #f1f5f9;
            border-radius: 3px;
            height: 12px;
            overflow: hidden;
            display: block;
        }

        .bar-fill {
            height: 12px;
            background-color: #3b82f6;
            border-radius: 3px;
        }

        .bar-fill-alt {
            height: 12px;
            background-color: #10b981;
            border-radius: 3px;
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
    </style>
</head>
<body>

    <div class="header-container">
        <div class="header-title">
            Relatório Gerencial — Visão Geral
            @if(!empty($partInfo))
                <span class="header-part">({{ $partInfo }})</span>
            @endif
        </div>
        <div class="header-period">Período de referência: <strong>{{ $periodLabel }}</strong></div>
    </div>

    @if (!empty($kpis))
        @php
            // KPIs já vêm formatados (strings, alguns com "R$") — 3 cards por linha
            $kpiChunks = collect($kpis)->chunk(3);
        @endphp

        @foreach ($kpiChunks as $kpiRow)
            <table class="kpi-table">
                <tr>
                    @foreach ($kpiRow as $kpi)
                        @php
                            $isMoney = str_contains($kpi['value'], 'R$');
                        @endphp
                        <td width="{{ 100 / $kpiRow->count() }}%">
                            <div class="kpi-card {{ $isMoney ? 'accent-green' : '' }}">
                                <div class="kpi-label">{{ $kpi['label'] }}</div>
                                <div class="kpi-value {{ $isMoney ? 'accent-green-text' : '' }}">{{ $kpi['value'] }}</div>
                            </div>
                        </td>
                    @endforeach
                </tr>
            </table>
        @endforeach
    @endif

    @if (!empty($brandsRanking) && count($brandsRanking) > 0)
        @php
            $maxBrandCount = collect($brandsRanking)->map(fn ($b) => (int) preg_replace('/\D/', '', $b['value']))->max() ?: 1;
        @endphp
        <div class="section-title">Top Marcas Atendidas (Gráfico de Volume)</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th width="25%">Marca</th>
                    <th width="15%" class="text-center">Qtd. Revisões</th>
                    <th width="60%">Proporção / Gráfico</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($brandsRanking as $brand)
                    @php
                        $count = (int) preg_replace('/\D/', '', $brand['value']);
                        $percentage = min(100, round(($count / $maxBrandCount) * 100));
                    @endphp
                    <tr>
                        <td class="font-bold">{{ $brand['label'] }}</td>
                        <td class="text-center"><span class="badge">{{ $brand['value'] }}</span></td>
                        <td>
                            <div class="bar-container">
                                <div class="bar-fill" style="width: {{ $percentage }}%;"></div>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if (!empty($peopleRanking) && count($peopleRanking) > 0)
        @php
            $maxPeopleCount = collect($peopleRanking)->map(fn ($p) => (int) preg_replace('/\D/', '', $p['value']))->max() ?: 1;
        @endphp
        <div class="section-title">Clientes Frequentes</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th width="35%">Cliente</th>
                    <th width="15%" class="text-center">Revisões</th>
                    <th width="50%">Frequência</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($peopleRanking as $person)
                    @php
                        $count = (int) preg_replace('/\D/', '', $person['value']);
                        $percentagePeople = min(100, round(($count / $maxPeopleCount) * 100));
                    @endphp
                    <tr>
                        <td>{{ $person['label'] }}</td>
                        <td class="text-center"><span class="badge">{{ $person['value'] }}</span></td>
                        <td>
                            <div class="bar-container">
                                <div class="bar-fill-alt" style="width: {{ $percentagePeople }}%;"></div>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if (!empty($genderBreakdown) && count($genderBreakdown) > 0)
        <div class="section-title">Distribuição por Gênero</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th width="35%">Grupo</th>
                    <th width="35%">Categoria</th>
                    <th width="30%" class="text-right">Quantidade</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($genderBreakdown as $row)
                    <tr>
                        <td class="font-bold">{{ $row[0] }}</td>
                        <td>{{ $row[1] }}</td>
                        <td class="text-right"><span class="badge">{{ $row[2] }}</span></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

</body>
</html>