<!DOCTYPE html>
<html>

<head>
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

        h2 {
            font-size: 16px;
            margin-bottom: 10px;
        }

        .meta {
            font-size: 10px;
            color: #666;
            margin-bottom: 15px;
        }
    </style>
</head>

<body>
    <h2>Products Export</h2>
    <div class="meta">Generated: {{ $generatedAt }}</div>
    
    <table>
        <thead>
            <tr>
                <th>Title</th>
                <th>Type</th>
                <th>Price</th>
                <th>Level</th>
                <th>Status</th>
                <th>Created At</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
                <tr>
                    <td>{{ $row['title'] }}</td>
                    <td>{{ $row['type'] }}</td>
                    <td>{{ $row['price'] }} {{ $row['currency'] }}</td>
                    <td>{{ $row['level'] }}</td>
                    <td>{{ $row['status'] }}</td>
                    <td>{{ $row['created_at'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>
