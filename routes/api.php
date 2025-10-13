<?php

use App\Http\Controllers\GuarantorController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
// use App\Http\Controllers\Auth\ForgotPasswordController;

Route::controller(UserController::class)->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/login', 'login');
        Route::post('/register', 'register');
        Route::post('/update-password', 'updatePassword');
        Route::post('/forgot-password','forgotPassword'); 
        // Route::post('/refresh', 'refreshToken');

        Route::group(['middleware' => ['auth:api']], function () {
            Route::get('/user', 'user');
            Route::post('/logout', 'logout');
        });
    });
});

// Route::post('/auth/forgot-password', [ForgotPasswordController::class, 'sendResetLink']);

Route::middleware(['auth:api', 'role:supervisor'])->prefix('companies')->group(function () {
    Route::post('/', [GuarantorController::class, 'listCompanies']);
    Route::get('/{id}', [GuarantorController::class, 'getCompany']);
    Route::post('/{id}/company_change_status', [GuarantorController::class, 'changeCompanyStatus']);
});
