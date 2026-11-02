<?php

use App\Http\Controllers\{AskController, AuthController, DashboardController, DocumentController, LoanController, RepaymentController};
use Illuminate\Support\Facades\Route;

Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login'])->middleware('throttle:10,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('loans', [LoanController::class, 'index']);
    Route::post('loans', [LoanController::class, 'store']);
    Route::get('loans/{loan}', [LoanController::class, 'show']);
    Route::patch('loans/{loan}/status', [LoanController::class, 'updateStatus']);
    Route::post('loans/{loan}/repayments', [RepaymentController::class, 'store']);
    Route::post('loans/{loan}/documents', [DocumentController::class, 'store']);
    Route::post('loans/{loan}/ask', AskController::class);
    Route::get('dashboard/summary', DashboardController::class);
});