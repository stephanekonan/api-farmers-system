<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\DebtController;
use App\Http\Controllers\Api\FarmerController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\RepaymentController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;


Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::post('logout-all', [AuthController::class, 'logoutAll'])->middleware('auth:sanctum');
    Route::get('me', [AuthController::class, 'me'])->middleware('auth:sanctum');
    Route::post('refresh', [AuthController::class, 'refresh'])->middleware('auth:sanctum');
    Route::get('sessions', [AuthController::class, 'sessions'])->middleware('auth:sanctum');
    Route::delete('revoke-session/{token}', [AuthController::class, 'revokeSession'])->middleware('auth:sanctum');
});

Route::prefix('users')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [UserController::class, 'index']);
    Route::post('/', [UserController::class, 'store']);
    Route::get('/{id}', [UserController::class, 'show']);
    Route::put('/{id}', [UserController::class, 'update']);
    Route::delete('/{id}', [UserController::class, 'destroy']);
    Route::get('/role/{role}', [UserController::class, 'getUsersByRole']);
});


Route::prefix('products')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [ProductController::class, 'index']);
    Route::post('/', [ProductController::class, 'store']);
    Route::get('/{id}', [ProductController::class, 'show']);
    Route::put('/{id}', [ProductController::class, 'update']);
    Route::delete('/{id}', [ProductController::class, 'destroy']);
    Route::get('/category/{categoryId}', [ProductController::class, 'getByCategory']);
    Route::get('/active', [ProductController::class, 'getActive']);
    Route::get('/search', [ProductController::class, 'search']);
});


Route::prefix('categories')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [CategoryController::class, 'index']);
    Route::post('/', [CategoryController::class, 'store']);
    Route::get('/{id}', [CategoryController::class, 'show']);
    Route::put('/{id}', [CategoryController::class, 'update']);
    Route::delete('/{id}', [CategoryController::class, 'destroy']);
    Route::get('/{id}/products', [CategoryController::class, 'getProducts']);
    Route::get('/root/root', [CategoryController::class, 'getRootCategories']);
    Route::get('/{id}/children', [CategoryController::class, 'getChildren']);
});


Route::prefix('farmers')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [FarmerController::class, 'index']);
    Route::post('/', [FarmerController::class, 'store']);
    Route::get('/{id}', [FarmerController::class, 'show']);
    Route::put('/{id}', [FarmerController::class, 'update']);
    Route::delete('/{id}', [FarmerController::class, 'destroy']);
    Route::get('/search', [FarmerController::class, 'search']);
    Route::get('/active', [FarmerController::class, 'getActive']);
    Route::get('/{id}/transactions', [FarmerController::class, 'getTransactions']);
    Route::get('/{id}/debts', [FarmerController::class, 'getDebts']);
    Route::get('/{id}/repayments', [FarmerController::class, 'getRepayments']);
    Route::get('/{id}/financial-summary', [FarmerController::class, 'getFinancialSummary']);
    Route::get('/{id}/debt-summary', [FarmerController::class, 'getDebtSummary']);
    Route::post('/{id}/update-credit-limit', [FarmerController::class, 'updateCreditLimit']);
    Route::post('/{id}/update-outstanding-debt', [FarmerController::class, 'updateOutstandingDebt']);
});


Route::prefix('transactions')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [TransactionController::class, 'index']);
    Route::post('/', [TransactionController::class, 'store']);
    Route::get('/{id}', [TransactionController::class, 'show']);
    Route::put('/{id}', [TransactionController::class, 'update']);
    Route::delete('/{id}', [TransactionController::class, 'destroy']);
    Route::get('/reference/{reference}', [TransactionController::class, 'findByReference']);
    Route::get('/farmer/{farmerId}', [TransactionController::class, 'getByFarmer']);
    Route::get('/operator/{operatorId}', [TransactionController::class, 'getByOperator']);
    Route::get('/status/{status}', [TransactionController::class, 'getByStatus']);
    Route::get('/date-range', [TransactionController::class, 'getByDateRange']);
    Route::post('/{transactionId}/items', [TransactionController::class, 'addItem']);
    Route::put('/items/{itemId}', [TransactionController::class, 'updateItem']);
    Route::delete('/items/{itemId}', [TransactionController::class, 'removeItem']);
    Route::get('/statistics', [TransactionController::class, 'statistics']);
    Route::get('/total', [TransactionController::class, 'getTotalByPeriod']);
});


Route::prefix('debts')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [DebtController::class, 'index']);
    Route::post('/', [DebtController::class, 'store']);
    Route::get('/{id}', [DebtController::class, 'show']);
    Route::put('/{id}', [DebtController::class, 'update']);
    Route::delete('/{id}', [DebtController::class, 'destroy']);
    Route::get('/farmer/{farmerId}', [DebtController::class, 'getByFarmer']);
    Route::get('/transaction/{transactionId}', [DebtController::class, 'getByTransaction']);
    Route::get('/outstanding', [DebtController::class, 'outstanding']);
    Route::get('/overdue', [DebtController::class, 'overdue']);
    Route::get('/paid', [DebtController::class, 'paid']);
    Route::post('/{id}/payment', [DebtController::class, 'addPayment']);
    Route::put('/{id}/update-remaining', [DebtController::class, 'updateRemainingAmount']);
    Route::put('/{id}/mark-fully-paid', [DebtController::class, 'markAsFullyPaid']);
    Route::get('/farmer/{farmerId}/summary', [DebtController::class, 'farmerSummary']);
    Route::get('/statistics', [DebtController::class, 'statistics']);
    Route::get('/total-outstanding', [DebtController::class, 'getTotalOutstanding']);
    Route::get('/total-paid', [DebtController::class, 'getTotalPaid']);
});


Route::prefix('repayments')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [RepaymentController::class, 'index']);
    Route::post('/', [RepaymentController::class, 'store']);
    Route::get('/{id}', [RepaymentController::class, 'show']);
    Route::put('/{id}', [RepaymentController::class, 'update']);
    Route::delete('/{id}', [RepaymentController::class, 'destroy']);
    Route::get('/reference/{reference}', [RepaymentController::class, 'findByReference']);
    Route::get('/farmer/{farmerId}', [RepaymentController::class, 'getByFarmer']);
    Route::get('/operator/{operatorId}', [RepaymentController::class, 'getByOperator']);
    Route::get('/date-range/date-range', [RepaymentController::class, 'getByDateRange']);
    Route::get('/statistics/statistics', [RepaymentController::class, 'statistics']);
    Route::get('/total', [RepaymentController::class, 'getTotalRepaidByPeriod']);
});
