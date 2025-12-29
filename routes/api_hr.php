<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\HR\CompanyController;
use App\Http\Controllers\HR\BranchController;
use App\Http\Controllers\HR\DepartmentController;
use App\Http\Controllers\HR\PositionController;
use App\Http\Controllers\HR\ShiftController;
use App\Http\Controllers\HR\GovernorateController;
use App\Http\Controllers\HR\CityController;
use App\Http\Controllers\HR\FormalOccasionController;
use App\Http\Controllers\HR\EmployeeController;
use App\Http\Controllers\HR\SettingController;
use App\Http\Controllers\HR\LocationController;
use App\Http\Controllers\HR\HeadMangerController;
use App\Http\Controllers\HR\AttendanceController;
use App\Http\Controllers\HR\AttendancePolicyController;
use App\Http\Controllers\HR\PayrollPolicyController;
use App\Http\Controllers\HR\AuthController;
use App\Http\Controllers\HR\MonthlyAttendanceController;
use App\Http\Controllers\HR\Sheets\AbsentSheetController;
use App\Http\Controllers\HR\Sheets\OverTimeSheetController;
use App\Http\Controllers\HR\Sheets\IncompleteShiftSheetController;
use App\Http\Controllers\HR\Sheets\AttendanceSheetController;
use App\Http\Middleware\CheckPermission;
use App\Http\Controllers\HR\HrController;
use App\Http\Controllers\HR\PermissionController;
use App\Http\Controllers\HR\EmployeeProfileController;
use App\Http\Controllers\ZKAttendanceController;
use App\Http\Controllers\HR\SalaryMethodController;
use App\Http\Controllers\HR\GeneralController;
use App\Http\Controllers\HR\RewardController;
use App\Http\Controllers\HR\PenaltyController;
use App\Http\Controllers\HR\LeaveController;
use App\Http\Controllers\HR\HolidayController;
use App\Http\Controllers\HR\RemotelyEmployeeController;
use App\Http\Controllers\HR\OvertimeController;
use App\Http\Controllers\HR\LoanController;
use App\Http\Controllers\HR\ResignationController;
use App\Http\Controllers\HR\BranchTransferController;
use App\Http\Controllers\HR\EmployeeKpiController;



Route::prefix('hr/v1')->group(function () {

    Route::post('login', [AuthController::class, 'login']);

    //home Home_header

    Route::get('home-header', [GeneralController::class, 'Home_header']);

    // Companies
    Route::apiResource('companies', CompanyController::class);

    // Branches
    Route::apiResource('branches', BranchController::class);

    // Positions
    Route::apiResource('positions', PositionController::class);

    // Shifts
    Route::apiResource('shifts', ShiftController::class);

    // Departments
    Route::apiResource('departments', DepartmentController::class);

    // Locations
    Route::get('countries', [LocationController::class, 'countries']);
    Route::get('countries/{country}/governorates', [LocationController::class, 'governorates']);

    // Formal occasions

    Route::get('/list', [EmployeeController::class, 'simpleList']);
    Route::get('/header', [EmployeeController::class, 'header']);
    // Employees
    Route::prefix('employees')->group(function () {
        Route::get('/', [EmployeeController::class, 'index']);
        Route::get('{id}', [EmployeeController::class, 'show']);
        Route::post('/', [EmployeeController::class, 'store']);
        Route::put('{id}', [EmployeeController::class, 'update']);
        Route::delete('{id}', [EmployeeController::class, 'destroy']);
        Route::post('/export-data', [EmployeeController::class, 'exportData']);
    });

    // Head Managers
    Route::get('/head-managers', [HeadMangerController::class, 'getManagers']);
    Route::post('/{id}/head-managers', [HeadMangerController::class, 'addAsManager']);
    Route::post('/{id}/remove-managers', [HeadMangerController::class, 'removeManager']);

    // Attendance & Policies
    Route::apiResource('attendances', AttendanceController::class);
    Route::delete('/attendances/{attendance}', [AttendanceController::class, 'destroy']);
    Route::patch('/attendances/{employeeId}', [AttendanceController::class, 'update']);
    Route::apiResource('attendance-policies', AttendancePolicyController::class);
    Route::get('{Id}/monthly-report/{month}', [AttendanceController::class, 'getMonhlyReport']);
    Route::get('/monthly-report/{month}', [MonthlyAttendanceController::class, 'getMonthlyReportAll']);
    Route::get('/monthly-report-pdf', [MonthlyAttendanceController::class, 'exportMonthlyReportAllPdf']);
    Route::get('/daily-report', [MonthlyAttendanceController::class, 'getDailyReport']);
    Route::get('/part-time-monthly-report/{month}', [MonthlyAttendanceController::class, 'partTimeHoursReport']);
    Route::get('/header/{day}', [AttendanceController::class, 'header']);
    // Sheets
    Route::post('absents', [AbsentSheetController::class, 'index']);
    Route::get('/absents/export-pdf', [AbsentSheetController::class, 'exportPdf']);
    Route::post('over-time', [OverTimeSheetController::class, 'index']);
    Route::patch('over-time/{id}', [OverTimeSheetController::class, 'update']);
    Route::delete('over-time/{id}', [OverTimeSheetController::class, 'delete']);
    Route::get('/over-time-export-pdf', [OverTimeSheetController::class, 'exportPdf']);
    Route::post('late-time', [IncompleteShiftSheetController::class, 'index']);
    Route::patch('late-time/{id}', [IncompleteShiftSheetController::class, 'update']);
    Route::delete('late-time/{id}', [IncompleteShiftSheetController::class, 'delete']);
    Route::get('/late-time-export-pdf', [IncompleteShiftSheetController::class, 'exportPdf']);
    Route::post('attendances-sheet', [AttendanceSheetController::class, 'index']);
    Route::patch('/attendances-sheet/{id}', [AttendanceSheetController::class, 'update']);
    Route::delete('attendances-sheet/{id}', [AttendanceSheetController::class, 'delete']);
    Route::get('/attendance-export-pdf', [AttendanceSheetController::class, 'exportPdf']);
    // Permissions management

    Route::get('/personal-data/{id}', [EmployeeProfileController::class, 'PersonalData']);
    Route::get('/activity/{id}', [EmployeeProfileController::class, 'PersonalActivity']);
    Route::patch('/personal-data/{id}', [EmployeeProfileController::class, 'update']);
    // Settings
    Route::get('/settings', [SettingController::class, 'index']);
    Route::post('/settings', [SettingController::class, 'store']);
    Route::delete('/settings', [SettingController::class, 'destroy']);
    Route::apiResource('salary-methods', SalaryMethodController::class);

    Route::get(
        '/attendance/daily-summary-cards',
        [GeneralController::class, 'getDailySummaryCards']
    );



    Route::get('/graph', [GeneralController::class, 'getLastMonthAttendanceSummary']);


    /////////////////////////v2

    Route::get('reward-summary', [RewardController::class, 'rewardsSummary']);
    Route::apiResource('rewards', RewardController::class);

    Route::get('penalty-summary', [PenaltyController::class, 'penaltiesSummary']);
    Route::apiResource('penalties', PenaltyController::class);

    Route::get('leaves-summary', [LeaveController::class, 'leavesSummary']);
    Route::apiResource('leaves', LeaveController::class);          ////leavesSummary


    Route::get('holidays-summary', [HolidayController::class, 'holidaysSummary']);
    Route::get('general-holidays/{year}', [HolidayController::class, 'egyptHolidays']);
    Route::apiResource('holidays', HolidayController::class);
    

    Route::get('remotely-summary', [RemotelyEmployeeController::class, 'remotelySummary']);
    Route::apiResource('remotely', RemotelyEmployeeController::class);

    Route::get('overtime-summary', [OvertimeController::class, 'overtimeSummary']);
    Route::apiResource('overtime', OvertimeController::class);

    Route::get('loans-summary', [LoanController::class, 'loansSummary']);
    Route::apiResource('loans', LoanController::class);

    Route::get('resignations-summary', [ResignationController::class, 'resignationsSummary']);
    Route::apiResource('resignations', ResignationController::class);

    Route::get('branch-transfers-summary', [BranchTransferController::class, 'branchTransfersSummary']);
    Route::apiResource('branch-transfers', BranchTransferController::class);


  //  bulkUpsert    EmployeeKpiController

    Route::post('add-kpi', [EmployeeKpiController::class, 'bulkUpsert']);

    
});




// HR Authentication Routes
// Route::prefix('hr')->group(function () {
//     Route::post('v1/login', [AuthController::class, 'login']);


//     // Protected HR routes
//     Route::middleware(['auth:hr-api'])->prefix('v1')->group(function () {
//         Route::get('me', [AuthController::class, 'me']);
//         Route::post('logout', [AuthController::class, 'logout']);
//         Route::post('refresh', [AuthController::class, 'refresh']);
           
 //Route::get('header', [GeneralController::class, 'Home_header']);

//         Route::middleware(['throttle:100,1'])->group(function () {

//             // Companies
//             Route::apiResource('companies', CompanyController::class)
//                 ->middleware([
//                     'index' => 'permission:view_companies',
//                     'store' => 'permission:add_company',
//                     'update' => 'permission:edit_company',
//                     'destroy' => 'permission:delete_company',
//                 ]);

//             // Branches
//             Route::apiResource('branches', BranchController::class)
//                 ->middleware([
//                     'index' => 'permission:view_branches',
//                     'store' => 'permission:add_branch',
//                     'update' => 'permission:edit_branch',
//                     'destroy' => 'permission:delete_branch',
//                 ]);

//             // Positions
//             Route::apiResource('positions', PositionController::class)
//                 ->middleware([
//                     'index' => 'permission:view_positions',
//                     'store' => 'permission:add_position',
//                     'update' => 'permission:edit_position',
//                     'destroy' => 'permission:delete_position',
//                 ]);

//             // Shifts
//             Route::apiResource('shifts', ShiftController::class)
//                 ->middleware([
//                     'index' => 'permission:view_shifts',
//                     'store' => 'permission:add_shift',
//                     'update' => 'permission:edit_shift',
//                     'destroy' => 'permission:delete_shift',
//                 ]);

//             // Departments
//             Route::apiResource('departments', DepartmentController::class)
//                 ->middleware([
//                     'index' => 'permission:view_departments',
//                     'store' => 'permission:add_department',
//                     'update' => 'permission:edit_department',
//                     'destroy' => 'permission:delete_department',
//                 ]);

//             // Locations
//             Route::get('countries', [LocationController::class, 'countries']);
//             Route::get('countries/{country}/governorates', [LocationController::class, 'governorates']);

//            
//            
//             Route::get('/list', [EmployeeController::class, 'simpleList']);
//             Route::get('/header', [EmployeeController::class, 'header']);
//             // Employees
//             Route::prefix('employees')->group(function () {
//                 Route::get('/', [EmployeeController::class, 'index'])
//                     ->middleware('permission:view_employees');
//                 Route::get('{id}', [EmployeeController::class, 'show'])
//                     ->middleware('permission:view_employee');
//                 Route::post('/', [EmployeeController::class, 'store'])
//                     ->middleware('permission:add_employee');
//                 Route::put('{id}', [EmployeeController::class, 'update'])
//                     ->middleware('permission:edit_employee');
//                 Route::delete('{id}', [EmployeeController::class, 'destroy'])
//                     ->middleware('permission:delete_employee');
//                 Route::post('/export-data', [EmployeeController::class, 'exportData'])
//                     ->middleware('permission:export_employee_data');
//             });

//             // Head Managers
//             Route::get('/head-managers', [HeadMangerController::class, 'getManagers']);
//             Route::post('/{id}/head-managers', [HeadMangerController::class, 'addAsManager'])
//                 ->middleware('permission:add_head_manager');
//             Route::post('/{id}/remove-managers', [HeadMangerController::class, 'removeManager']);

//             // Attendance & Policies
//             Route::apiResource('attendances', AttendanceController::class)
//                 ->middleware([
//                     'index' => 'permission:view_attendances',
//                     'store' => 'permission:add_attendance',

//                     'destroy' => 'permission:delete_attendance',
//                 ]);
//             Route::delete('/attendances', [AttendanceController::class, 'destroy']);
//             Route::patch('/attendances/{employeeId}', [AttendanceController::class, 'update']);


//             Route::apiResource('attendance-policies', AttendancePolicyController::class)
//                 ->middleware([
//                     'index' => 'permission:view_attendance_policies',
//                     'store' => 'permission:add_attendance_policy',
//                     'update' => 'permission:edit_attendance_policy',
//                     'destroy' => 'permission:delete_attendance_policy',
//                 ]);

//             Route::get('{Id}/monthly-report/{month}', [AttendanceController::class, 'getMonhlyReport'])
//                 ->middleware('permission:view_monthly_report');
//             Route::get('/monthly-report/{month}', [MonthlyAttendanceController::class, 'getMonthlyReportAll'])
//                 ->middleware('permission:view_monthly_report_all');
//             Route::get('/monthly-report-pdf', [MonthlyAttendanceController::class, 'exportMonthlyReportAllPdf'])
//                 ->middleware('permission:export_monthly_report_pdf');
//             Route::get('/daily-report', [MonthlyAttendanceController::class, 'getDailyReport'])
//                 ->middleware('permission:getDailyReport');

//             Route::get('/part-time-monthly-report/{month}', [MonthlyAttendanceController::class, 'partTimeHoursReport']);



//             Route::get('/header/{day}', [AttendanceController::class, 'header']);
                   

//             // Sheets
//             Route::post('absents', [AbsentSheetController::class, 'index'])
//                 ->middleware('permission:view_absent_sheet');
//             Route::get('/absents/export-pdf', [AbsentSheetController::class, 'exportPdf'])
//                 ->middleware('permission:export_absent_pdf');

//             Route::post('over-time', [OverTimeSheetController::class, 'index'])
//                 ->middleware('permission:view_overtime_sheet');
//             Route::patch('over-time/{id}', [OverTimeSheetController::class, 'update'])
//                 ->middleware('permission:edit_overtime_sheet');
//             Route::delete('over-time/{id}', [OverTimeSheetController::class, 'delete'])
//                 ->middleware('permission:delete_overtime_sheet');
//             Route::get('/over-time-export-pdf', [OverTimeSheetController::class, 'exportPdf'])
//                 ->middleware('permission:export_overtime_pdf');

//             Route::post('late-time', [IncompleteShiftSheetController::class, 'index'])
//                 ->middleware('permission:view_late_sheet');
//             Route::patch('late-time/{id}', [IncompleteShiftSheetController::class, 'update'])
//                 ->middleware('permission:edit_late_sheet');
//             Route::delete('late-time/{id}', [IncompleteShiftSheetController::class, 'delete'])
//                 ->middleware('permission:delete_late_sheet');
//             Route::get('/late-time-export-pdf', [IncompleteShiftSheetController::class, 'exportPdf'])
//                 ->middleware('permission:export_late_pdf');

//             Route::post('attendances-sheet', [AttendanceSheetController::class, 'index'])
//                 ->middleware('permission:view_attendance_sheet');
//             Route::patch('/attendances-sheet/{id}', [AttendanceSheetController::class, 'update'])
//                 ->middleware('permission:edit_attendance_sheet');
//             Route::delete('attendances-sheet/{id}', [AttendanceSheetController::class, 'delete'])
//                 ->middleware('permission:delete_attendance_sheet');
//             Route::get('/attendance-export-pdf', [AttendanceSheetController::class, 'exportPdf'])
//                 ->middleware('permission:export_attendance_pdf');

//            
//           

//             Route::get('/personal-data/{id}', [EmployeeProfileController::class, 'PersonalData']);
//             Route::get('/activity/{id}', [EmployeeProfileController::class, 'PersonalActivity']);
//             Route::patch('/personal-data/{id}', [EmployeeProfileController::class, 'update']);

    //                 Route::get('/attendance/daily-summary-cards', [GeneralController::class, 'getDailySummaryCards']);

    //  Route::get('/graph',[GeneralController::class, 'getLastMonthAttendanceSummary'] );


//             // Settings
//             Route::get('/settings', [SettingController::class, 'index'])
//                 ->middleware('permission:view_settings');
//             Route::post('/settings', [SettingController::class, 'store'])
//                 ->middleware('permission:add_setting');
//             Route::delete('/settings', [SettingController::class, 'destroy'])
//                 ->middleware('permission:delete_setting');

//             Route::apiResource('salary-methods', SalaryMethodController::class);
               
//           // routes/api.php


//         });
//     });


//     Route::post('/attendance/import', [ZKAttendanceController::class, 'import']);
// });
