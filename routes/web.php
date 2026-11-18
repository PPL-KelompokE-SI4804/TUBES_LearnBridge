<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CourseApiController;
use App\Http\Controllers\PortalController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => auth()->check() ? redirect('/dashboard') : redirect('/login'));
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class,'loginForm'])->name('login');
    Route::post('/login', [AuthController::class,'login']);
    Route::get('/register', [AuthController::class,'registerForm']);
    Route::post('/register', [AuthController::class,'register']);
});
Route::get('/sertifikat/verifikasi/{code}', [PortalController::class,'verifyCertificate'])->name('certificate.verify');
Route::get('/profil/publik/{id}', [PortalController::class,'publicProfile']);
Route::get('/institusi/publik/{id}', [PortalController::class,'publicInstitution']);

// PBI 7 - API pengelolaan kursus. Endpoint institusi dan admin memakai sesi
// autentikasi aplikasi yang sama dengan portal web.
Route::prefix('api/v1')->group(function () {
    Route::get('/courses', [CourseApiController::class, 'catalog']);
    Route::get('/courses/{id}', [CourseApiController::class, 'publicShow'])->whereNumber('id');

    Route::middleware(['auth', 'role:institusi'])->prefix('institution/courses')->group(function () {
        Route::post('/', [CourseApiController::class, 'store']);
        Route::get('/', [CourseApiController::class, 'index']);
        Route::get('/{id}', [CourseApiController::class, 'show'])->whereNumber('id');
        Route::patch('/{id}', [CourseApiController::class, 'update'])->whereNumber('id');
        Route::post('/{id}/publish', [CourseApiController::class, 'publish'])->whereNumber('id');
        Route::post('/{id}/archive', [CourseApiController::class, 'archive'])->whereNumber('id');
        Route::delete('/{id}', [CourseApiController::class, 'destroy'])->whereNumber('id');
    });

    Route::middleware(['auth', 'role:admin'])->prefix('admin/courses')->group(function () {
        Route::get('/', [CourseApiController::class, 'adminIndex']);
        Route::get('/{id}', [CourseApiController::class, 'adminShow'])->whereNumber('id');
        Route::patch('/{id}/status', [CourseApiController::class, 'updateStatus'])->whereNumber('id');
    });
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class,'logout']);
    Route::get('/dashboard', [PortalController::class,'index']);
    Route::post('/profil', [PortalController::class,'updateProfile']);
    Route::post('/profil/institusi', [PortalController::class,'updateInstitutionProfile']);
    Route::get('/modul/{module}', [PortalController::class,'show'])->whereAlpha('module');
    Route::post('/modul/{module}', [PortalController::class,'store'])->whereAlpha('module');
    Route::put('/modul/{module}/{id}', [PortalController::class,'update'])->whereAlpha('module');
    Route::delete('/modul/{module}/{id}', [PortalController::class,'destroy'])->whereAlpha('module');
    Route::post('/pendaftaran', [PortalController::class,'enroll']);
    Route::post('/kuis/{quiz}/kumpulkan', [PortalController::class,'submitQuiz']);
    Route::post('/admin/institusi/{id}/verifikasi', [PortalController::class,'verifyInstitution']);
    Route::post('/admin/laporan/{id}/tindak-lanjut', [PortalController::class,'resolveReport']);
});
