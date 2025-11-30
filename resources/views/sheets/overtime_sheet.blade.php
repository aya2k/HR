<!DOCTYPE html>
<html>
<head>
    <title>Overtime Sheet</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            font-size: 14px; 
            color: #333; 
        }
        h2 { 
            text-align: center; 
            color: #27ae60; /* عنوان باللون الأخضر */
            margin-bottom: 20px; 
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 10px; 
        }
        th, td { 
            border: 1px solid #ccc; 
            padding: 10px; 
            text-align: left; 
            color: #1a1a1a; /* نصوص الجدول باللون الأخضر */
        }
        th { 
            background-color: #d5f5e3; /* خلفية فاتحة للـ header */
            color: #145a32; /* نص الـ header باللون الأخضر الغامق */
        }
        tr:nth-child(even) { 
            background-color: #eafaf1; /* خلفية فاتحة للصفوف الزوجية */
        }
        tr:hover { 
            background-color: #d4efdf; /* لون عند المرور على الصف */
        }
    </style>
</head>
<body>

    <h2>
        Overtime Sheet for {{ $employee->first_name }} {{ $employee->last_name }} - {{ \Carbon\Carbon::parse($month)->format('F Y') }}
    </h2>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>End Shift Time</th>
                <th>Check Out</th>
                <th>Overtime (Minutes)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sheet as $row)
                <tr>
                    <td>{{ $row['date'] }}</td>
                    <td>{{ $row['end_shift_time'] }}</td>
                    <td>{{ $row['check_out'] }}</td>
                    <td>{{ $row['overtime'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>
