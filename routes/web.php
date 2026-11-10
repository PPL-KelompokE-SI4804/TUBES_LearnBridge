<?php

use App\Http\Controllers\AuthController;
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
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class,'logout']);
    Route::get('/dashboard', [PortalController::class,'index']);
    Route::post('/profil', [PortalController::class,'updateProfile']);
    Route::get('/modul/{module}', [PortalController::class,'show'])->whereAlpha('module');
    Route::post('/modul/{module}', [PortalController::class,'store'])->whereAlpha('module');
    Route::put('/modul/{module}/{id}', [PortalController::class,'update'])->whereAlpha('module');
    Route::delete('/modul/{module}/{id}', [PortalController::class,'destroy'])->whereAlpha('module');
    Route::post('/pendaftaran', [PortalController::class,'enroll']);
    Route::post('/kuis/{quiz}/kumpulkan', [PortalController::class,'submitQuiz']);
    Route::post('/admin/institusi/{id}/verifikasi', [PortalController::class,'verifyInstitution']);
    Route::post('/admin/laporan/{id}/tindak-lanjut', [PortalController::class,'resolveReport']);
});
