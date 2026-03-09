<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\Api\Company\CompanyAccountController as CompanyAccountController;
use App\Http\Controllers\Api\Company\VacancyController as CompanyVacancyController;
use App\Http\Controllers\Api\Coordinator\CoordinatorVacancyController;
use App\Http\Controllers\Api\CoordinatorRegisterController;
use App\Http\Controllers\Api\StageCoordinatorUserController;
use App\Http\Controllers\Api\TagController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Public: register as stage coordinator
    Route::post('auth/register/coordinator', [CoordinatorRegisterController::class, 'registerCoordinator']);

    // Public: login (returns JWT)
    Route::post('auth/login', [AuthController::class, 'login']);

    // Protected: auth (JWT)
    Route::middleware('auth:api')->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::post('auth/refresh', [AuthController::class, 'refresh']);
        Route::get('auth/me', [AuthController::class, 'me']);

        // Tags: list for selecting when creating vacancies (any authenticated user)
        Route::get('tags', [TagController::class, 'index']);

        // Company-only: own company, profile, and vacancies (full CRUD)
        Route::middleware('company')->group(function () {
            Route::get('company', [CompanyAccountController::class, 'showCompany']);
            Route::match(['put', 'patch'], 'company', [CompanyAccountController::class, 'updateCompany']);
            Route::get('company/profile', [CompanyAccountController::class, 'showProfile']);
            Route::match(['put', 'patch'], 'company/profile', [CompanyAccountController::class, 'updateProfile']);
            Route::get('company/vacancies', [CompanyVacancyController::class, 'index']);
            Route::post('company/vacancies', [CompanyVacancyController::class, 'store']);
            Route::get('company/vacancies/{vacancy}', [CompanyVacancyController::class, 'show']);
            Route::match(['put', 'patch'], 'company/vacancies/{vacancy}', [CompanyVacancyController::class, 'update']);
            Route::delete('company/vacancies/{vacancy}', [CompanyVacancyController::class, 'destroy']);
        });

        // Coordinator-only: list vacancies, CRUD companies, CRUD users (students + company users)
        Route::middleware('coordinator')->group(function () {
            Route::get('coordinator/vacancies', [CoordinatorVacancyController::class, 'index']);
            Route::apiResource('coordinator/companies', CompanyController::class);
            Route::apiResource('coordinator/users', StageCoordinatorUserController::class);
        });
    });
});
