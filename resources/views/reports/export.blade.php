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
            border-spacing: 6px 0;
            margin-bottom: 20px;
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

        .badge-price {
            background-color: #dcfce7;
            color: #15803d;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
    </style>
</head>
<body>

    <div class="header-container">
        <div class="header-title">
            Relatório Gerencial
            @if(!empty($partInfo))
                <span class="header-part">({{ $partInfo }})</span>
            @endif
        </div>
        <div class="header-period">Período de referência: <strong>{{ $periodLabel }}</strong></div>
    </div>

    @if (!empty($summary))
        <table class="kpi-table">
            <tr>
                <td width="25%">
                    <div class="kpi-card">
                        <div class="kpi-label">Revisões Realizadas</div>
                        <div class="kpi-value">{{ number_format($summary->total_revisions ?? 0, 0, ',', '.') }}</div>
                    </div>
                </td>
                <td width="25%">
                    <div class="kpi-card">
                        <div class="kpi-label">Veículos Atendidos</div>
                        <div class="kpi-value">{{ number_format($summary->vehicles_count ?? 0, 0, ',', '.') }}</div>
                    </div>
                </td>
                <td width="25%">
                    <div class="kpi-card">
                        <div class="kpi-label">Clientes Únicos</div>
                        <div class="kpi-value">{{ number_format($summary->people_count ?? 0, 0, ',', '.') }}</div>
                    </div>
                </td>
                <td width="25%">
                    <div class="kpi-card" style="border-left-color: #10b981;">
                        <div class="kpi-label">Faturamento / Custo</div>
                        <div class="kpi-value" style="color: #047857;">R$ {{ number_format($summary->total_cost ?? 0, 2, ',', '.') }}</div>
                    </div>
                </td>
            </tr>
        </table>
    @endif

    @if (!empty($brandsRanking) && count($brandsRanking) > 0)
        @php
            $maxBrandCount = $brandsRanking->max('count') ?: 1;
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
                @foreach ($brandsRanking->take(10) as $row)
                    @php
                        $percentage = min(100, round(($row->count / $maxBrandCount) * 100));
                    @endphp
                    <tr>
                        <td class="font-bold">{{ $row->brand }}</td>
                        <td class="text-center"><span class="badge">{{ $row->count }}</span></td>
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
            $maxPeopleCount = $peopleRanking->max('count') ?: 1;
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
                @foreach ($peopleRanking->take(10) as $row)
                    @php
                        $percentagePeople = min(100, round(($row->count / $maxPeopleCount) * 100));
                    @endphp
                    <tr>
                        <td>{{ $row->person_name }}</td>
                        <td class="text-center"><span class="badge">{{ $row->count }}</span></td>
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

    @if (!empty($revisionsRows) && count($revisionsRows) > 0)
        <div class="section-title">Detalhamento de Revisões</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th width="12%">Data</th>
                    <th width="25%">Cliente</th>
                    <th width="20%">Veículo</th>
                    <th width="28%">Descrição</th>
                    <th width="15%" class="text-right">Custo</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($revisionsRows as $row)
                    <tr>
                        <td>{{ !empty($row->date) ? \Carbon\Carbon::parse($row->date)->format('d/m/Y') : '-' }}</td>
                        <td class="font-bold">{{ $row->person_name ?? '-' }}</td>
                        <td>{{ $row->vehicle ?? '-' }}</td>
                        <td>{{ $row->description ?? '-' }}</td>
                        <td class="text-right">
                            <span class="badge badge-price">R$ {{ number_format($row->cost ?? 0, 2, ',', '.') }}</span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if (!empty($vehicleRows) && count($vehicleRows) > 0)
        <div class="section-title">Frota de Veículos Cadastrada</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th width="30%">Proprietário</th>
                    <th width="20%">Placa</th>
                    <th width="25%">Modelo</th>
                    <th width="25%">Marca</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($vehicleRows as $row)
                    <tr>
                        <td>{{ $row->person_name }}</td>
                        <td><span class="badge">{{ $row->plate }}</span></td>
                        <td>{{ $row->model }}</td>
                        <td class="font-bold">{{ $row->brand }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if (!empty($peopleRows) && count($peopleRows) > 0)
        <div class="section-title">Cadastro de Clientes</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th width="40%">Nome</th>
                    <th width="35%">E-mail</th>
                    <th width="25%">Telefone</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($peopleRows as $row)
                    <tr>
                        <td class="font-bold">{{ $row->name }}</td>
                        <td>{{ $row->email }}</td>
                        <td>{{ $row->phone }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

</body>
</html>