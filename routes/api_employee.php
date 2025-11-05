<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Employee\ApplicantController;








Route::middleware(['throttle:10,1'])->group(function () {

    Route::post('/applicants/parse-cv', [ApplicantController::class, 'parseCV']);
    Route::post('/applicants/confirm', [ApplicantController::class, 'confirmApplicant']);

   
});
