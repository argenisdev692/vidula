<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 11px;
            color: #333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th {
            background-color: #f3f4f6;
            color: #333;
            text-align: center;
            padding: 8px;
            border: 1px solid #e5e7eb;
            font-weight: bold;
        }

        td {
            padding: 8px;
            border: 1px solid #e5e7eb;
            text-align: center;
            vertical-align: middle;
        }

        tr:nth-child(even) {
            background-color: #fafafa;
        }

        .meta {
            margin-top: 8px;
            margin-bottom: 12px;
        }
    </style>
</head>
<body>
    <h2>{{ $title }}</h2>
    <p class="meta">Generated: {{ $generatedAt }} · Total: {{ count($rows) }} records</p>

    <table>
        <thead>
            <tr>
                <th>UUID</th>
                <th>Name</th>
                <th>Guard</th>
                <th>Roles</th>
                <th>Roles Count</th>
                <th>Created At</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
                <tr>
                    <td>{{ $row['uuid'] }}</td>
                    <td>{{ $row['name'] }}</td>
                    <td>{{ $row['guard_name'] }}</td>
                    <td>{{ $row['roles'] }}</td>
                    <td>{{ $row['roles_count'] }}</td>
                    <td>{{ $row['created_at'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
