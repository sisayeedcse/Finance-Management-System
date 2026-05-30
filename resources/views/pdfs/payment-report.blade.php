<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>SIPR Payment Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            color: #333;
        }

        h1 {
            text-align: center;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }

        .summary {
            margin-top: 20px;
            font-size: 14px;
        }
    </style>
</head>

<body>
    <h1>SIPR Group - Payment Report</h1>
    <p><strong>Month:</strong> {{ $month_label }} {{ $year }}</p>

    <table>
        <thead>
            <tr>
                <th>Member</th>
                <th>Status</th>
                <th>Amount Due</th>
                <th>Amount Paid</th>
                <th>Date Paid</th>
            </tr>
        </thead>
        <tbody>
            @foreach($members as $m)
                @php
                    $p = $payments[$m->id] ?? null;
                @endphp
                <tr>
                    <td>{{ $m->name }}</td>
                    <td>{{ $p?->status ?? 'pending' }}</td>
                    <td>BDT {{ number_format($m->monthly_due, 0) }}</td>
                    <td>BDT {{ number_format($p?->amount ?? 0, 0) }}</td>
                    <td>{{ $p?->paid_at ?? '–' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary">
        <p><strong>Total Due:</strong> BDT {{ number_format($total_due, 0) }}</p>
        <p><strong>Total Collected:</strong> BDT {{ number_format($collected, 0) }}</p>
    </div>
</body>

</html>