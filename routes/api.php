<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;

Route::controller(UserController::class)->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/login', 'login');
        Route::post('/register', 'register');
        Route::get('/reset-password/{token}/{email}', 'reset_password')->name('password.reset');
        Route::post('/update-password', 'updatePassword');
        Route::post('/forgot-password','forgotPassword');
//        Route::post('/refresh', 'refreshToken');

        Route::group(['middleware' => ['auth:api']], function () {
            Route::get('/user', 'user');
            Route::post('/logout', 'logout');
        });
    });
});
