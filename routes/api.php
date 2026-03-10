<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\Api\Student\StudentSavedVacancyController;
use App\Http\Controllers\Api\Company\CompanyAccountController as CompanyAccountController;
use App\Http\Controllers\Api\Company\VacancyController as CompanyVacancyController;
use App\Http\Controllers\Api\Coordinator\CoordinatorVacancyController;
use App\Http\Controllers\CompanyRegistrationController;
use App\Http\Controllers\Api\CoordinatorRegisterController;
use App\Http\Controllers\Api\PublicCompanyController;
use App\Http\Controllers\Api\StageCoordinatorUserController;
use App\Http\Controllers\Api\Student\StudentProfileController;
use App\Http\Controllers\Api\TagController;
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

        // Student-only: own profile, experiences, preferences, languages, tags
        Route::middleware('student')->group(function () {
            // Profile (user + student_profile)
            Route::get('student/profile', [StudentProfileController::class, 'show']);
            Route::match(['put', 'patch'], 'student/profile', [StudentProfileController::class, 'update']);

            // Preferences
            Route::get('student/preferences', [StudentProfileController::class, 'showPreferences']);
            Route::match(['put', 'patch'], 'student/preferences', [StudentProfileController::class, 'updatePreferences']);

            // Experiences (CRUD)
            Route::get('student/experiences', [StudentProfileController::class, 'listExperiences']);
            Route::post('student/experiences', [StudentProfileController::class, 'storeExperience']);
            Route::match(['put', 'patch'], 'student/experiences/{experience}', [StudentProfileController::class, 'updateExperience']);
            Route::delete('student/experiences/{experience}', [StudentProfileController::class, 'destroyExperience']);

            // Languages (sync)
            Route::get('student/languages', [StudentProfileController::class, 'listLanguages']);
            Route::put('student/languages', [StudentProfileController::class, 'syncLanguages']);

            // Tags/Skills (sync)
            Route::get('student/tags', [StudentProfileController::class, 'listTags']);
            Route::put('student/tags', [StudentProfileController::class, 'syncTags']);

            Route::get('student/saved-vacancies', [StudentSavedVacancyController::class, 'index']);
            Route::post('student/saved-vacancies', [StudentSavedVacancyController::class, 'store']);
            Route::delete('student/saved-vacancies/{vacancyId}', [StudentSavedVacancyController::class, 'destroy']);
        });

        // Coordinator-only: list vacancies, CRUD companies, CRUD users (students + company users)
        Route::middleware('coordinator')->group(function () {
            Route::get('coordinator/vacancies', [CoordinatorVacancyController::class, 'index']);
            Route::apiResource('coordinator/companies', CompanyController::class);
            Route::apiResource('coordinator/users', StageCoordinatorUserController::class);
        });
    });
});
