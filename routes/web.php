<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\InstitutionVerificationController;
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
    Route::middleware('role:admin')->group(function () {
        Route::get('/admin/verifikasi-institusi', [InstitutionVerificationController::class, 'index']);
        Route::get('/admin/verifikasi-institusi/{id}', [InstitutionVerificationController::class, 'show'])->whereNumber('id');
        Route::post('/admin/verifikasi-institusi/{id}/setujui', [InstitutionVerificationController::class, 'approve'])->whereNumber('id');
        Route::post('/admin/verifikasi-institusi/{id}/tolak', [InstitutionVerificationController::class, 'reject'])->whereNumber('id');
        Route::get('/admin/kategori', [CategoryController::class, 'index']);
        Route::get('/admin/kategori/tambah', [CategoryController::class, 'create']);
        Route::post('/admin/kategori', [CategoryController::class, 'store']);
        Route::get('/admin/kategori/{id}/ubah', [CategoryController::class, 'edit'])->whereNumber('id');
        Route::put('/admin/kategori/{id}', [CategoryController::class, 'update'])->whereNumber('id');
        Route::delete('/admin/kategori/{id}', [CategoryController::class, 'destroy'])->whereNumber('id');
    });
});
