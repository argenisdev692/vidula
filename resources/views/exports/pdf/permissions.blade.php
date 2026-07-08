<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Permissions export</title>
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { font-size: 11px; color: #1f2937; }
        h1 { font-size: 16px; margin: 0 0 2px; }
        .meta { color: #6b7280; font-size: 10px; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 6px 8px; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
        th { background: #f3f4f6; text-transform: uppercase; font-size: 9px; letter-spacing: .04em; }
        tr:nth-child(even) td { background: #fafafa; }
    </style>
</head>
<body>
    <h1>{{ config('app.name') }} — Permissions</h1>
    <div class="meta">Generated at {{ $generatedAt }}</div>

    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Guard</th>
                <th>Roles</th>
                <th>Created</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    <td>{{ $row['Name'] }}</td>
                    <td>{{ $row['Guard'] }}</td>
                    <td>{{ $row['Roles'] }}</td>
                    <td>{{ $row['Created'] }}</td>
                    <td>{{ $row['Status'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
