<!DOCTYPE html>
<html>
<head>
    <title>Attendance Sheet</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 14px; color: #333; }
        h2 { text-align: center; color: #1a73e8; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ccc; padding: 10px; text-align: left; }
        th { background-color: #e8f0fe; color: #1a1a1a; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        tr:hover { background-color: #f1f1f1; }
    </style>
</head>
<body>

    <h2>
        Attendance Sheet for {{ $employee->first_name }} {{ $employee->last_name }} - {{ \Carbon\Carbon::parse($month)->format('F Y') }}
    </h2>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Check In</th>
                <th>Check Out</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sheet as $row)
                <tr>
                    <td>{{ $row['date'] }}</td>
                    <td>{{ $row['check_in'] }}</td>
                    <td>{{ $row['check_out'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>
