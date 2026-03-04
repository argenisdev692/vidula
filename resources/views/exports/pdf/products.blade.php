<!DOCTYPE html>
<html>

<head>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }

        table,
        th,
        td {
            border: 1px solid black;
        }

        th,
        td {
            padding: 8px;
            text-align: center;
            vertical-align: middle;
        }
    </style>
</head>

<body>
    <h2>Products List</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Price</th>
                <th>SKU</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
                <tr>
                    <td>{{ $item->id ?? '' }}</td>
                    <td>{{ $item->name ?? '' }}</td>
                    <td>{{ $item->price ?? '' }}</td>
                    <td>{{ $item->sku ?? '' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>