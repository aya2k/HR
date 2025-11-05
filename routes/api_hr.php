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
use App\Http\Controllers\HR\SettingController;
use App\Http\Controllers\HR\LocationController;

Route::middleware(['throttle:10,1'])->group(function () {

    Route::apiResource('companies', CompanyController::class);  //done
    Route::apiResource('branches', BranchController::class);  //done
    
    Route::apiResource('positions', PositionController::class);  //done
    Route::apiResource('shifts', ShiftController::class);
    Route::get('countries', [LocationController::class, 'countries']);  //done
    Route::get('countries/{country}/governorates', [LocationController::class, 'governorates']);  //done
   // Route::get('governorates/{governorate}/cities', [LocationController::class, 'cities']);

    Route::apiResource('formal-occasions', FormalOccasionController::class);
  //  Route::post('applicants/{id}/hire', [EmployeeController::class, 'storeFullApplication']);



    Route::prefix('employees')->group(function () {
        Route::get('/', [EmployeeController::class, 'index']);                                 //done
        Route::get('{id}', [EmployeeController::class, 'show']);                                //done
        Route::post('/', [EmployeeController::class, 'store']);       //add employee            //done
        Route::put('{id}', [EmployeeController::class, 'update']);                              //done
        Route::delete('{id}', [EmployeeController::class, 'destroy']);                          //done



        Route::get('/settings', [SettingController::class, 'index']);
        Route::post('/settings', [SettingController::class, 'store']);
        Route::delete('/settings', [SettingController::class, 'destroy']);
    });
});
