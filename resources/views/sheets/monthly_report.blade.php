<!DOCTYPE html>
<html>
<head>
    <title>Monthly Attendance Report - All Employees</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #333; }
        h2 { text-align: center; color: #1a73e8; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: center; }
        th { background-color: #e8f0fe; color: #1a1a1a; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        tr:hover { background-color: #f1f1f1; }
    </style>
</head>
<body>

    <h2>Monthly Attendance Report - All Employees</h2>
    <p style="text-align:center;">
        Month: {{ \Carbon\Carbon::parse($month)->format('F Y') }}
        @if($from || $to)
            | Period: {{ $from ?? $month.'-01' }} to {{ $to ?? \Carbon\Carbon::parse($month)->endOfMonth()->toDateString() }}
        @endif
    </p>

    <table>
        <thead>
            <tr>
                <th>Employee Code</th>
                <th>Employee Name</th>
                <th>Position</th>
                <th>Present Days</th>
                <th>Absent Days</th>
                <th>Days with Incomplete Shifts</th>
                <th>Total Incomplete Minutes</th>
                <th>Days with Overtime</th>
                <th>Total Overtime Minutes</th>
            </tr>
        </thead>
        <tbody>
            @foreach($summary as $row)
                <tr>
                    <td>{{ $row['employee_code'] }}</td>
                    <td>{{ $row['employee_name'] }}</td>
                    <td>{{ $row['employee_position'] }}</td>
                    <td>{{ $row['present_days'] }}</td>
                    <td>{{ $row['absent_days'] }}</td>
                    <td>{{ $row['days_with_incomplete_shifts'] }}</td>
                    <td>{{ $row['total_incomplete_shifts'] }}</td>
                    <td>{{ $row['days_with_overtime'] }}</td>
                    <td>{{ $row['total_overtime_minutes'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>
