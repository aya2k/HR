<!DOCTYPE html>
<html>
<head>
    <title>Late Sheet</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 14px; color: #333; }
        h2 { text-align: center; color: #e67e22; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ccc; padding: 10px; text-align: left; }
        th { background-color: #fceabb; color: #333; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        tr:hover { background-color: #f1f1f1; }
    </style>
</head>
<body>

    <h2>
        Late Attendance Sheet for {{ $employee->first_name }} {{ $employee->last_name }} - {{ \Carbon\Carbon::parse($month)->format('F Y') }}
    </h2>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Start Shift Time</th>
                <th>Check In</th>
                <th>Late Minutes</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sheet as $row)
                <tr>
                    <td>{{ $row['date'] }}</td>
                    <td>{{ $row['start_shift_time'] }}</td>
                    <td>{{ $row['check_in'] }}</td>
                    <td>{{ $row['difference'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>
