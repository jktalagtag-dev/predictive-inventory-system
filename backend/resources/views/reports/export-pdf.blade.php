<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 11px; color: #1e293b; }
        h1 { font-size: 16px; margin-bottom: 2px; }
        p.meta { color: #64748b; margin-top: 0; margin-bottom: 14px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #cbd5e1; padding: 4px 6px; text-align: left; }
        th { background-color: #f1f5f9; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <p class="meta">
        Generated {{ $meta['generatedAt'] }} &middot; Timezone {{ $meta['timezone'] }} &middot;
        Data cutoff {{ $meta['dataCutoffAt'] }} &middot; {{ ucfirst($meta['freshness']) }} data
    </p>
    <table>
        <thead>
        <tr>
            @foreach ($columns as $column)
                <th>{{ $column }}</th>
            @endforeach
        </tr>
        </thead>
        <tbody>
        @forelse ($rows as $row)
            <tr>
                @foreach ($columns as $column)
                    <td>{{ $row[$column] ?? '' }}</td>
                @endforeach
            </tr>
        @empty
            <tr><td colspan="{{ count($columns) }}">No data for the selected filters.</td></tr>
        @endforelse
        </tbody>
    </table>
</body>
</html>
