<?php

use App\Http\Controllers\Api\CoordinatorRegisterController;
use Illuminate\Support\Facades\Route;

Route::post('/register/coordinator', [CoordinatorRegisterController::class, 'registerCoordinator']);
Route::get('/test', function () {
    return response()->json([
        'message' => 'API werkt'
    ]);
});
