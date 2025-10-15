<?php

use App\Http\Controllers\GuarantorController;
use App\Http\Controllers\StudyProgramController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CompanyController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;

Route::controller(UserController::class)->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/login', 'login');
        Route::post('/register', 'register');
        Route::post('/update-password', 'updatePassword');
        Route::post('/forgot-password','forgotPassword');
//        Route::post('/refresh', 'refreshToken');

        Route::group(['middleware' => ['auth:api']], function () {
            Route::post('/change-password', 'changePassword');
            Route::get('/user', 'user');
            Route::post('/logout', 'logout');
        });
    });
});


Route::middleware(['auth:api', 'role:supervisor'])->prefix('companies')->group(function () {
    Route::post('/', [GuarantorController::class, 'listCompanies']);
    Route::get('/{id}', [GuarantorController::class, 'getCompany']);
    Route::post('/{id}/company_change_status', [GuarantorController::class, 'changeCompanyStatus']);
});

Route::get('/company/activate/{token}', [CompanyController::class, 'activate']);

Route::controller(StudyProgramController::class)->prefix('study-programs')->group(function () {
    Route::get('/index', 'index');
    Route::group(['middleware' => ['auth:api']], function () {

    });
});
