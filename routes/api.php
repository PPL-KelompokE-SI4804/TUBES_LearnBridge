<?php

use App\Http\Controllers\Api\V1\EnrollmentController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('api.basic')->group(function () {
    Route::middleware('role:pelajar')->group(function () {
        Route::post('/courses/{courseId}/enrollments', [EnrollmentController::class, 'storeCourse'])->whereNumber('courseId');
        Route::post('/bootcamps/{bootcampId}/enrollments', [EnrollmentController::class, 'storeBootcamp'])->whereNumber('bootcampId');
        Route::get('/users/me/enrollments', [EnrollmentController::class, 'indexMine']);
        Route::get('/users/me/enrollments/{id}', [EnrollmentController::class, 'showMine'])->whereNumber('id');
        Route::patch('/users/me/enrollments/{id}', [EnrollmentController::class, 'updateMine'])->whereNumber('id');
        Route::post('/users/me/enrollments/{id}/cancel', [EnrollmentController::class, 'cancelMine'])->whereNumber('id');
    });

    Route::middleware('role:institusi')->prefix('institution')->group(function () {
        Route::get('/enrollments', [EnrollmentController::class, 'indexForInstitution']);
        Route::get('/enrollments/{id}', [EnrollmentController::class, 'showForInstitution'])->whereNumber('id');
    });
});
