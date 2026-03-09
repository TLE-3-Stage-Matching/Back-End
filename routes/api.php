<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CompanyRegistrationController;
use App\Http\Controllers\Api\CoordinatorRegisterController;
use App\Http\Controllers\Api\PublicCompanyController;
use App\Http\Controllers\Api\StageCoordinatorUserController;
use App\Http\Controllers\Api\VacancyController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Public: register as stage coordinator
    Route::post('auth/register/coordinator', [CoordinatorRegisterController::class, 'registerCoordinator']);

    // Public: company self-registration (company created with is_active=false until coordinator approves)
    Route::post('auth/register/company', [CompanyRegistrationController::class, 'store']);

    // Public: login (returns JWT)
    Route::post('auth/login', [AuthController::class, 'login']);

    // Public: list active companies only (for student/public frontends)
    Route::get('companies', [PublicCompanyController::class, 'index']);

    // Public: list vacancies from active companies only (for student/public frontends)
    Route::get('vacancies', [VacancyController::class, 'index']);

    // Protected: auth (JWT)
    Route::middleware('auth:api')->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::post('auth/refresh', [AuthController::class, 'refresh']);
        Route::get('auth/me', [AuthController::class, 'me']);

        // Coordinator-only: CRUD companies, then CRUD users (students + company users)
        Route::middleware('coordinator')->group(function () {
            Route::apiResource('coordinator/companies', CompanyController::class);
            Route::apiResource('coordinator/users', StageCoordinatorUserController::class);
        });
    });
});
