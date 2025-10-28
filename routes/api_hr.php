<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\HR\CompanyController;
use App\Http\Controllers\HR\BranchController;
use App\Http\Controllers\HR\DepartmentController;
use App\Http\Controllers\HR\PositionController;
use App\Http\Controllers\Hr\ShiftController;
use App\Http\Controllers\HR\GovernorateController;
use App\Http\Controllers\HR\CityController;
use App\Http\Controllers\HR\FormalOccasionController;
use App\Http\Controllers\HR\EmployeeController;



Route::apiResource('companies', CompanyController::class);
Route::apiResource('branches', BranchController::class);
Route::apiResource('departments', DepartmentController::class);
Route::apiResource('positions', PositionController::class);
Route::apiResource('shifts', ShiftController::class);
Route::apiResource('governorates', GovernorateController::class);
Route::apiResource('cities', CityController::class);
Route::apiResource('formal-occasions', FormalOccasionController::class); 
Route::post('applicants/{id}/hire', [EmployeeController::class, 'hireApplicant']);



Route::prefix('employees')->group(function () {
    Route::get('/', [EmployeeController::class, 'index']);       
    Route::get('{id}', [EmployeeController::class, 'show']);      
    Route::post('/', [EmployeeController::class, 'store']);     
    Route::put('{id}', [EmployeeController::class, 'update']);    
    Route::delete('{id}', [EmployeeController::class, 'destroy']); 
});



