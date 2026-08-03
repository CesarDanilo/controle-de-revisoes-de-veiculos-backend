<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #1f2937; }
        h1 { font-size: 18px; margin-bottom: 4px; }
        .part-info { font-size: 10px; color: #9ca3af; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { padding: 6px 8px; border-bottom: 1px solid #e5e7eb; text-align: left; font-size: 11px; }
        th { background: #f3f4f6; font-weight: 600; }
        tr:nth-child(even) { background: #fafafa; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>

    @if(!empty($partInfo))
        <div class="part-info">{{ $partInfo }}</div>
    @endif

    <table>
        <thead>
            <tr>
                @foreach($columns as $column)
                    <th>{{ $column['label'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    @foreach($columns as $column)
                        <td>{{ is_object($row) ? ($row->{$column['key']} ?? '—') : ($row[$column['key']] ?? '—') }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columns) }}">Nenhum registro encontrado.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>