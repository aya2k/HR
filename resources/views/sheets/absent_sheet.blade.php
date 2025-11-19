<<!DOCTYPE html>
<html>
<head>
    <title>Absent Sheet</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            font-size: 14px; 
            color: #333; 
        }
        h2 {
            text-align: center;
            color: #e74c3c; /* لون أحمر للعنوان */
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
            color: #1a1a1a; /* لون أحمر للنصوص داخل الجدول */
        }
        th {
            background-color: #f9e6e6; /* خلفية فاتحة وردية/حمراء */
            color: #c0392b; /* نص أحمر */
        }
        tr:nth-child(even) {
            background-color: #fceaea; /* خلفية فاتحة للصفوف الزوجية */
        }
        tr:hover {
            background-color: #f2c6c6; /* لون عند المرور */
        }
    </style>
</head>
<body>

   <h2>
    Absent Sheet for {{ $employeeName }} - {{ \Carbon\Carbon::parse($month)->format('F Y') }}
</h2>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Reason</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sheet as $row)
                <tr>
                    <td>{{ $row['date'] }}</td>
                    <td>{{ $row['reason'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>
