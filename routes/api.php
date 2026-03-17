<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\Api\Student\StudentSavedVacancyController;
use App\Http\Controllers\Api\Company\CompanyAccountController as CompanyAccountController;
use App\Http\Controllers\Api\Company\VacancyController as CompanyVacancyController;
use App\Http\Controllers\Api\Coordinator\CoordinatorMatchController;
use App\Http\Controllers\Api\Coordinator\CoordinatorVacancyController;
use App\Http\Controllers\Api\AdminApiKeyController;
use App\Http\Controllers\Api\DevApiKeyController;
use App\Http\Controllers\CompanyRegistrationController;
use App\Http\Controllers\Api\CoordinatorRegisterController;
use App\Http\Controllers\Api\PublicCompanyController;
use App\Http\Controllers\Api\StageCoordinatorUserController;
use App\Http\Controllers\Api\Student\StudentMatchScoreController;
use App\Http\Controllers\Api\Student\StudentProfileController;
use App\Http\Controllers\Api\Student\StudentVacancyMatchController;
use App\Http\Controllers\Api\LanguageController;
use App\Http\Controllers\Api\LanguageLevelController;
use App\Http\Controllers\Api\TagController;
use App\Http\Controllers\Api\VacancyController;
use App\Http\Controllers\Api\Student\StudentFavoriteCompanyController;
use App\Http\Controllers\Api\Student\StudentMatchChoiceController;
use App\Http\Controllers\Api\Coordinator\CoordinatorMatchChoiceController;
use App\Http\Controllers\Api\Company\CompanyMatchChoiceController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Student\StudentProfileViewController;

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
            Route::get('student/profile', [StudentProfileController::class, 'show']);
            Route::match(['put', 'patch'], 'student/profile', [StudentProfileController::class, 'update']);
            Route::get('student/preferences', [StudentProfileController::class, 'showPreferences']);
            Route::match(['put', 'patch'], 'student/preferences', [StudentProfileController::class, 'updatePreferences']);
            Route::get('student/experiences', [StudentProfileController::class, 'listExperiences']);
            Route::post('student/experiences', [StudentProfileController::class, 'storeExperience']);
            Route::match(['put', 'patch'], 'student/experiences/{experience}', [StudentProfileController::class, 'updateExperience']);
            Route::delete('student/experiences/{experience}', [StudentProfileController::class, 'destroyExperience']);
            Route::get('student/languages', [StudentProfileController::class, 'listLanguages']);
            Route::put('student/languages', [StudentProfileController::class, 'syncLanguages']);
            Route::get('student/tags', [StudentProfileController::class, 'listTags']);
            Route::put('student/tags', [StudentProfileController::class, 'syncTags']);
        });

        // Coordinator-only: list vacancies, CRUD companies, CRUD users
        Route::middleware('coordinator')->group(function () {
            Route::get('coordinator/vacancies', [CoordinatorVacancyController::class, 'index']);
            Route::apiResource('coordinator/companies', CompanyController::class);
            Route::apiResource('coordinator/users', StageCoordinatorUserController::class);
        });
    });
});

Route::prefix('v2')->middleware('api-key')->group(function () {
    Route::post('auth/register/coordinator', [CoordinatorRegisterController::class, 'registerCoordinator']);
    Route::post('auth/register/company', [CompanyRegistrationController::class, 'store']);
    Route::post('auth/login', [AuthController::class, 'login']);
    Route::get('companies', [PublicCompanyController::class, 'index']);
    Route::get('vacancies', [VacancyController::class, 'index']);

    Route::middleware('auth:api')->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::post('auth/refresh', [AuthController::class, 'refresh']);
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::get('tags', [TagController::class, 'index']);
        Route::get('languages', [LanguageController::class, 'index']);
        Route::get('language-levels', [LanguageLevelController::class, 'index']);

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
            Route::get('company/vacancies/{vacancy}/comments', [CompanyVacancyController::class, 'listComments']);
            Route::patch('company/vacancies/comments/{comment}', [CompanyVacancyController::class, 'updateComment']);
            Route::delete('company/vacancies/comments/{comment}', [CompanyVacancyController::class, 'destroyComment']);
            Route::get('company/match-choices', [CompanyMatchChoiceController::class, 'index']);
            Route::patch('company/match-choices/{choice}/approve', [CompanyMatchChoiceController::class, 'approve']);
            Route::patch('company/match-choices/{choice}/reject', [CompanyMatchChoiceController::class, 'reject']);
        });

        Route::middleware('student')->group(function () {
            Route::get('student/profile', [StudentProfileController::class, 'show']);
            Route::match(['put', 'patch'], 'student/profile', [StudentProfileController::class, 'update']);
            Route::get('student/preferences', [StudentProfileController::class, 'showPreferences']);
            Route::match(['put', 'patch'], 'student/preferences', [StudentProfileController::class, 'updatePreferences']);
            Route::get('student/experiences', [StudentProfileController::class, 'listExperiences']);
            Route::post('student/experiences', [StudentProfileController::class, 'storeExperience']);
            Route::match(['put', 'patch'], 'student/experiences/{experience}', [StudentProfileController::class, 'updateExperience']);
            Route::delete('student/experiences/{experience}', [StudentProfileController::class, 'destroyExperience']);
            Route::get('student/languages', [StudentProfileController::class, 'listLanguages']);
            Route::put('student/languages', [StudentProfileController::class, 'syncLanguages']);
            Route::get('student/favorite-companies', [StudentFavoriteCompanyController::class, 'index']);
            Route::post('student/favorite-companies', [StudentFavoriteCompanyController::class, 'store']);
            Route::delete('student/favorite-companies/{companyId}', [StudentFavoriteCompanyController::class, 'destroy']);
            Route::get('student/saved-vacancies', [StudentSavedVacancyController::class, 'index']);
            Route::post('student/saved-vacancies', [StudentSavedVacancyController::class, 'store']);
            Route::delete('student/saved-vacancies/{vacancyId}', [StudentSavedVacancyController::class, 'destroy']);
            Route::get('student/tags', [StudentProfileController::class, 'listTags']);
            Route::put('student/tags', [StudentProfileController::class, 'syncTags']);
            Route::get('student/vacancies/top-matches', [StudentVacancyMatchController::class, 'topMatches']);
            Route::get('student/vacancies/with-scores', [StudentVacancyMatchController::class, 'withScores']);
            Route::get('student/vacancies/{vacancy}/detail', [StudentVacancyMatchController::class, 'detail']);
            Route::get('student/vacancies-with-scores', [StudentMatchScoreController::class, 'vacanciesWithScores']);
            Route::get('student/match-choices', [StudentMatchChoiceController::class, 'index']);
            Route::post('student/match-choices', [StudentMatchChoiceController::class, 'store']);
            Route::get('student/match-choices/{choice}', [StudentMatchChoiceController::class, 'show']);
            Route::match(['put', 'patch'], 'student/match-choices/{choice}', [StudentMatchChoiceController::class, 'update']);
        });

        Route::get('student/{student}', [StudentProfileViewController::class, 'show'])
            ->whereNumber('student');

        Route::middleware('coordinator')->group(function () {
            Route::get('coordinator/vacancies', [CoordinatorVacancyController::class, 'index']);
            Route::get('coordinator/students/{user}/vacancies-with-scores', [CoordinatorMatchController::class, 'studentVacanciesWithScores']);
            Route::apiResource('coordinator/companies', CompanyController::class);
            Route::apiResource('coordinator/users', StageCoordinatorUserController::class);
            Route::post('coordinator/users/{student}/assignments', [StageCoordinatorUserController::class, 'assignCoordinator']);
            Route::post('coordinator/users/{student}/unassignments', [StageCoordinatorUserController::class, 'unassignCoordinator']);
            Route::post('coordinator/vacancies/{vacancy}/comments', [CoordinatorVacancyController::class, 'storeComment']);
            Route::get('coordinator/match-choices', [CoordinatorMatchChoiceController::class, 'index']);
            Route::patch('coordinator/match-choices/{choice}/approve', [CoordinatorMatchChoiceController::class, 'approve']);
            Route::patch('coordinator/match-choices/{choice}/reject', [CoordinatorMatchChoiceController::class, 'reject']);
        });
    });
});

// Dev-only: manage v2 API keys (no API key required, only dev JWT)
Route::prefix('v2')->middleware(['auth:api', 'dev'])->group(function () {
    Route::get('dev/api-keys', [DevApiKeyController::class, 'index']);
    Route::post('dev/api-keys', [DevApiKeyController::class, 'store']);
});

// Admin-only: list and revoke all API keys (no API key required, only admin JWT)
Route::prefix('v2')->middleware(['auth:api', 'admin'])->group(function () {
    Route::get('admin/api-keys', [AdminApiKeyController::class, 'index']);
    Route::delete('admin/api-keys/{apiKey}', [AdminApiKeyController::class, 'destroy']);
});

