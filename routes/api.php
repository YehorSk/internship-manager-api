<?php

use App\Http\Controllers\PracticeController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudyProgramController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ReportController;
use Laravel\Passport\Http\Middleware\CheckToken;
use Laravel\Passport\Http\Middleware\EnsureClientIsResourceOwner;
use Laravel\Passport\Passport;

Route::controller(UserController::class)->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/login', 'login');
        Route::post('/register', 'register');
        Route::post('/update-password', 'updatePassword');
        Route::post('/forgot-password', 'forgotPassword');

        Route::group(['middleware' => ['auth:api', 'language']], function () {
            Route::post('/change-password', 'changePassword');
            Route::get('/user', 'user');
            Route::post('/logout', 'logout');
            Route::post('/update-profile', 'updateProfile');
            Route::put('/update-language', 'updateLanguage');
        });
    });
});

Route::middleware(['auth:api', 'language'])
    ->prefix('practices')
    ->controller(PracticeController::class)->group(function () {
        Route::middleware(['role:supervisor,company'])->group(function () {
            Route::get('/statistics', 'statistics');
            Route::patch('/{id}/update-document-status', 'updateDocumentStatus');
        });
        Route::middleware(['role:supervisor,company,student'])->group(function () {
            Route::post('/list', 'list');
            Route::get('/{id}', 'show');
            Route::get('/{id}/download-document', 'downloadDocument');
        });
        Route::middleware(['role:student'])->group(function () {
            Route::post('/', 'store')->middleware('ensure.company.active');
            Route::post('/upload-document', 'uploadDocument')->middleware('ensure.company.active');
            Route::get('/{id}/download-agreement', 'downloadAgreement');
            Route::get('/{id}/agreement-confirmation-request', 'agreementConfirmationRequest')->middleware('ensure.company.active');
            Route::get('/{id}/report-confirmation-request', 'reportConfirmationRequest')->middleware('ensure.company.active');
            Route::get('/{id}/download-report', 'downloadReport');
        });
        Route::middleware(['role:supervisor,student'])->group(function () {
            Route::delete('/{id}', 'destroy');
            Route::put('/{id}', 'update');
            Route::delete('/{id}/delete-document', 'deleteDocument');
        });
        Route::middleware(['role:supervisor'])->group(function () {
            Route::patch('/{id}/update-practice-status', 'updatePracticeStatus');
        });
    });

Route::middleware(['auth:api', 'language'])
    ->prefix('companies')
    ->controller(CompanyController::class)->group(function () {
        Route::middleware(['role:supervisor'])->group(function () {
            Route::post('/list', 'list');
            Route::get('/{id}', 'show');
            Route::patch('/{id}', 'update');
        });
    });

Route::middleware(['auth:api'])
    ->prefix('reports')
    ->controller(ReportController::class)->group(function () {
        Route::middleware(['role:supervisor'])->group(function () {
            Route::post('/list', 'list');
            Route::post('/generate', 'generate');
            Route::get('/academic-years', 'academicYears');
            Route::get('/{id}/download', 'download');
            Route::get('/{id}', 'show');
        });
    });

Route::controller(CompanyController::class)->prefix('company')->group(function () {
    Route::get('/activate/{token}', 'activate');
    Route::post('/activate-data/{token}', 'activateWithData');

    Route::group(['middleware' => ['auth:api']], function () {
        Route::get('/search', 'search');
    });
});

Route::middleware(['auth:api', 'language'])
    ->prefix('students')
    ->controller(StudentController::class)->group(function () {
        Route::middleware(['role:supervisor,company'])->group(function () {
            Route::get('/search', 'search');
            Route::post('/list', 'list');
            Route::get('/{id}', 'show');
        });
    });

Route::controller(StudyProgramController::class)->prefix('study-programs')->group(function () {
    Route::get('/index', 'index');
});

Route::prefix('practices')->controller(PracticeController::class)->group(function () {
    Route::post('/index', 'practiceListForExternalSystem')
        ->middleware('client:client:practice_list');

    Route::patch('/{id}/update-defense-status', 'updateDefenseStatus')
        ->middleware('client:client:practice_update_status');
});
