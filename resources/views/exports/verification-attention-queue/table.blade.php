<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $meta['title'] ?? 'Verification Attention Queue' }}</title>
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
    </style>
</head>
<body>
    <div class="heading">
        <h1>{{ $meta['title'] ?? 'Verification Attention Queue' }}</h1>
        <p>Generated {{ $meta['generated_at'] ?? now()->format('M d, Y h:i A') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Patient</th>
                <th>Clinic</th>
                <th>Priority</th>
                <th>SLA</th>
                <th>Due</th>
                <th>Assignee</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row['Patient'] ?? '-' }}</td>
                    <td>{{ $row['Clinic'] ?? '-' }}</td>
                    <td>{{ $row['Priority'] ?? '-' }}</td>
                    <td>{{ $row['SLA'] ?? '-' }}</td>
                    <td>{{ $row['Due'] ?? '-' }}</td>
                    <td>{{ $row['Assignee'] ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">No queue rows matched the selected dashboard filter.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
