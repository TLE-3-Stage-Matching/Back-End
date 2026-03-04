<?php

use App\Http\Controllers\Api\RegisterController;
use Illuminate\Support\Facades\Route;

Route::post('/register/coordinator', [RegisterController::class, 'registerCoordinator']);
Route::get('/test', function () {
    return response()->json([
        'message' => 'API werkt'
    ]);
});
