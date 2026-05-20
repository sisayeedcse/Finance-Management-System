<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>SIPR Passbook - {{ $member->name }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            color: #333;
        }

        h1 {
            text-align: center;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
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
    <h1>SIPR Group - Passbook</h1>
    <div class="header">
        <p><strong>Member:</strong> {{ $member->name }}</p>
        <p><strong>Member ID:</strong> {{ $member->id }}</p>
        <p><strong>Balance:</strong> ৳{{ number_format($wallet['balance'], 0) }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Amount</th>
                <th>Note</th>
            </tr>
        </thead>
        <tbody>
            @forelse($wallet['passbook'] as $tx)
                <tr>
                    <td>{{ $tx['date'] }}</td>
                    <td>{{ ucfirst($tx['type']) }}</td>
                    <td>৳{{ number_format($tx['amount'], 0) }}</td>
                    <td>{{ $tx['note'] ?? '–' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="text-align: center;">No transactions</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="summary">
        <p><strong>Total Deposited:</strong> ৳{{ number_format($wallet['deposited'], 0) }}</p>
        <p><strong>Total Fines:</strong> ৳{{ number_format($wallet['fines'], 0) }}</p>
        <p><strong>Current Balance:</strong> ৳{{ number_format($wallet['balance'], 0) }}</p>
    </div>
</body>

</html>