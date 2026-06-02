<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $meta['title'] ?? 'Verification Reports' }}</title>
    <style>
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 12px;
            color: #0f172a;
        }
        .heading {
            margin-bottom: 18px;
        }
        .heading h1 {
            margin: 0 0 6px 0;
            font-size: 24px;
        }
        .heading p {
            margin: 0;
            color: #64748b;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #dbe4ee;
            padding: 8px 10px;
            text-align: left;
            vertical-align: top;
        }
        th {
            background: #eff6ff;
            font-weight: 700;
        }
        tr:nth-child(even) td {
            background: #fbfdff;
        }
        .meta {
            display: flex;
            gap: 18px;
            margin-bottom: 14px;
            flex-wrap: wrap;
        }
        .meta-item {
            font-size: 12px;
            color: #475569;
        }
        .meta-item strong {
            color: #0f172a;
        }
    </style>
</head>
<body>
    <div class="heading">
        <h1>{{ $meta['title'] ?? 'Verification Reports' }}</h1>
        <p>Generated {{ $meta['generated_at'] ?? now()->format('M d, Y h:i A') }}</p>
    </div>

    <div class="meta">
        @if (! empty($meta['scope']))
            <div class="meta-item"><strong>Scope:</strong> {{ $meta['scope'] }}</div>
        @endif
        @if (! empty($meta['date_range']))
            <div class="meta-item"><strong>Date Range:</strong> {{ $meta['date_range'] }}</div>
        @endif
        <div class="meta-item"><strong>Rows:</strong> {{ count($rows) }}</div>
    </div>

    <table>
        <thead>
            <tr>
                @foreach (array_keys($headings) as $heading)
                    <th>{{ $heading }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    @foreach (array_keys($headings) as $heading)
                        <td>{{ $row[$heading] ?? '-' }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($headings) }}">No report rows matched the selected filters.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
