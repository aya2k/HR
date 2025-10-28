<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Employee\ApplicantController;

Route::post('/applicants/parse-cv', [ApplicantController::class, 'parseCV']);
Route::post('/applicants/confirm', [ApplicantController::class, 'confirmApplicant']);
