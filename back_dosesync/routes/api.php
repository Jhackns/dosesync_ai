<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MedicationController;
use App\Http\Controllers\DoseLogController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\CaregiverController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\PermissionsController;
use App\Http\Controllers\GeminiController;

// Public auth endpoints
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes (Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    // Utility endpoints
    Route::get('/user', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Role-restricted endpoints for Paciente
    Route::middleware('role:Paciente')->group(function () {
        Route::apiResource('medications', MedicationController::class);
        Route::get('/dose_logs', [DoseLogController::class, 'index']);
        Route::post('/log_dose', [DoseLogController::class, 'logDose']);
        Route::get('/doses/scheduled', [DoseLogController::class, 'getScheduledDoses']);
        Route::get('/report/export', [ReportController::class, 'exportComplianceCSV']);
        Route::get('/report/tres', [ReportController::class, 'getTRESReport']);
        Route::post('/caregiver/invite', [CaregiverController::class, 'inviteCaregiver']);
    });

    // Role-restricted endpoints for Admin
    Route::middleware('role:Admin')->group(function () {
        Route::apiResource('users', UsersController::class);
        // Roles management
        Route::get('/roles', [RolesController::class, 'index']);
        Route::post('/roles', [RolesController::class, 'store']);
        Route::get('/roles/{id}', [RolesController::class, 'show']);
        Route::patch('/roles/{id}', [RolesController::class, 'update']);

        // Permissions listing
        Route::get('/permissions/rol', [PermissionsController::class, 'index']);
        Route::get('/permissions/rol/{id}', [PermissionsController::class, 'show']);
    });

    // Role-restricted endpoints for Cuidador
    Route::middleware('role:Cuidador')->group(function () {
        Route::get('/caregiver/report/{patient}', [CaregiverController::class, 'getPatientReport']);
    });
});

Route::get('/gemini/check-models', [GeminiController::class, 'checkModels']);
