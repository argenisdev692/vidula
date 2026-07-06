<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Activity log export</title>
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { font-size: 11px; color: #1f2937; }
        h1 { font-size: 16px; margin: 0 0 2px; }
        .meta { color: #6b7280; font-size: 10px; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 6px 8px; border-bottom: 1px solid #e5e7eb; }
        th { background: #f3f4f6; text-transform: uppercase; font-size: 9px; letter-spacing: .04em; }
        tr:nth-child(even) td { background: #fafafa; }
    </style>
</head>
<body>
    <h1>{{ config('app.name') }} — Activity Log</h1>
    <div class="meta">Generated at {{ $generatedAt }}</div>

    <table>
        <thead>
            <tr>
                <th>When</th>
                <th>Actor</th>
                <th>Event</th>
                <th>Description</th>
                <th>Subject</th>
                <th>Log</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    <td>{{ $row['When'] }}</td>
                    <td>{{ $row['Actor'] }}</td>
                    <td>{{ $row['Event'] }}</td>
                    <td>{{ $row['Description'] }}</td>
                    <td>{{ $row['Subject'] }}</td>
                    <td>{{ $row['Log'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
