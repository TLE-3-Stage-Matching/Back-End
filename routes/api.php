<?php

use App\Http\Controllers\CompanyRegistrationController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\Api\CoordinatorRegisterController;


Route::prefix('v1')->group(function () {
    Route::post('auth/register-company', [CompanyRegistrationController::class, 'store']);
    Route::post('auth/login', [AuthController::class, 'login']);

    Route::middleware('auth:api')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::post('auth/refresh', [AuthController::class, 'refresh']);
        Route::apiResource('companies', CompanyController::class);
    });


    Route::post('/register/coordinator', [CoordinatorRegisterController::class, 'registerCoordinator']);
    Route::get('/test', function () {
        return response()->json([
            'message' => 'API werkt'
        ]);
    });
});
