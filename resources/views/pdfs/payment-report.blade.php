<!doctype html>
<html>
<head>
    <meta charset="utf-8" />
    <title>Payments Report - {{ $month_label }} {{ $year }}</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; }
        h1 { text-align: center; margin-bottom: 8px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #333; padding: 6px; text-align: left; }
        th { background: #f0f0f0; }
        .summary { margin-top: 12px; }
    </style>
</head>
<body>
    <h1>SIPR Group — Payments Report</h1>
    <div style="text-align:center">{{ $month_label }} {{ $year }}</div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Member</th>
                <th>Status</th>
                <th>Amount Due</th>
                <th>Amount Paid</th>
                <th>Date Paid</th>
            </tr>
        </thead>
        <tbody>
            @foreach($members as $i => $m)
                @php $p = $payments[$m->id] ?? null; @endphp
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $m->name }}</td>
                    <td>{{ $p?->status ?? 'pending' }}</td>
                    <td>{{ number_format($m->monthly_due,2) }}</td>
                    <td>{{ number_format($p?->amount ?? 0,2) }}</td>
                    <td>{{ $p?->paid_at ? \\Carbon\\Carbon::parse($p->paid_at)->format('Y-m-d') : '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary">
        <strong>Total Due:</strong> {{ number_format($total_due,2) }} &nbsp;&nbsp;
        <strong>Collected:</strong> {{ number_format($collected,2) }}
    </div>
</body>
</html>
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