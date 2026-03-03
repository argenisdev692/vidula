<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>{{ $title ?? 'Students Report' }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #333;
        }

        h2 {
            color: #1a1a2e;
            border-bottom: 2px solid #3B6EF8;
            padding-bottom: 8px;
            margin-bottom: 4px;
        }

        .meta {
            font-size: 10px;
            color: #666;
            margin-bottom: 16px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        th {
            background: #3B6EF8;
            color: #ffffff;
            padding: 8px 6px;
            text-align: left;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td {
            padding: 6px;
            border-bottom: 1px solid #e0e0e0;
            font-size: 11px;
        }

        tr:nth-child(even) {
            background: #f8f9fa;
        }

        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .badge-active {
            background: #d4edda;
            color: #155724;
        }

        .badge-inactive {
            background: #fff3cd;
            color: #856404;
        }

        .badge-draft {
            background: #d1ecf1;
            color: #0c5460;
        }

        .badge-graduated {
            background: #e2d5f1;
            color: #4a2078;
        }

        .badge-suspended {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
</head>

<body>
    <h2>{{ $title ?? 'Students Report' }}</h2>
    <p class="meta">Generated: {{ $generatedAt ?? now()->format('F j, Y H:i') }} · Total: {{ count($rows) }} records</p>

    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>DNI</th>
                <th>Status</th>
                <th>Active</th>
                <th>Created</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $row->name }}</td>
                    <td>{{ $row->email ?? '—' }}</td>
                    <td>{{ $row->phone ?? '—' }}</td>
                    <td>{{ $row->dni ?? '—' }}</td>
                    <td>
                        @php
                            $statusClass = match (strtolower($row->status ?? 'draft')) {
                                'active' => 'badge-active',
                                'inactive' => 'badge-inactive',
                                'graduated' => 'badge-graduated',
                                'suspended' => 'badge-suspended',
                                default => 'badge-draft',
                            };
                        @endphp
                        <span class="badge {{ $statusClass }}">{{ $row->status ?? 'DRAFT' }}</span>
                    </td>
                    <td>{{ $row->active ? 'Yes' : 'No' }}</td>
                    <td>{{ $row->created_at?->format('Y-m-d') ?? '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; color: #999; padding: 20px;">No students found</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>

</html>