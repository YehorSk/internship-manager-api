<?php

use App\Http\Controllers\PracticeController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudyProgramController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CompanyController;

Route::controller(UserController::class)->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/login', 'login');
        Route::post('/register', 'register');
        Route::post('/update-password', 'updatePassword');
        Route::post('/forgot-password','forgotPassword');

        Route::group(['middleware' => ['auth:api']], function () {
            Route::post('/change-password', 'changePassword');
            Route::get('/user', 'user');
            Route::post('/logout', 'logout');
            Route::post('/update-profile', 'updateProfile');
        });
    });
});

Route::middleware(['auth:api'])
    ->prefix('practices')
    ->controller(PracticeController::class)->group(function () {
        Route::middleware(['role:supervisor,company,student'])->group(function () {
            Route::post('/list', 'list');
            Route::get('/{id}', 'show');
            Route::get('/{id}/download-document', 'downloadDocument');
        });
        Route::middleware(['role:student'])->group(function () {
            Route::post('/', 'store');
            Route::post('/upload-document', 'uploadDocument');
            Route::get('/{id}/download-agreement', 'downloadAgreement');
            Route::get('/{id}/agreement-confirmation-request', 'agreementConfirmationRequest');
            Route::get('/{id}/report-confirmation-request', 'reportConfirmationRequest');
            Route::get('/{id}/download-report', 'downloadReport');
        });
        Route::middleware(['role:supervisor,company'])->group(function () {
            Route::patch('/{id}/update-document-status', 'updateDocumentStatus');
        });
        Route::middleware(['role:supervisor,student'])->group(function () {
            Route::delete('/{id}', 'destroy');
            Route::put('/{id}', 'update');
            Route::delete('/{id}/delete-document', 'deleteDocument');
        });
});

Route::middleware(['auth:api'])
    ->prefix('companies')
    ->controller(CompanyController::class)->group(function () {
        Route::middleware(['role:supervisor'])->group(function () {
            Route::post('/list', 'list');
            Route::get('/{id}', 'show');
            Route::put('/{id}', 'update');
            Route::patch('/{id}', 'update');
        });
});

Route::controller(CompanyController::class)->prefix('company')->group(function () {
    Route::get('/activate/{token}', 'activate');

    Route::group(['middleware' => ['auth:api']], function () {
        Route::get('/search/{value}', 'search');
    });
});

Route::middleware(['auth:api'])
    ->prefix('students')
    ->controller(StudentController::class)->group(function () {
    Route::middleware(['role:supervisor,company'])->group(function () {
        Route::get('/search/{value}', 'search');
    });
        Route::middleware(['role:supervisor'])->group(function () {
            Route::post('/list', 'list');
        });
});

Route::controller(StudyProgramController::class)->prefix('study-programs')->group(function () {
    Route::get('/index', 'index');
    Route::group(['middleware' => ['auth:api']], function () {

    });
});
