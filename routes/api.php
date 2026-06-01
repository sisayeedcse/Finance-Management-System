<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\NoticeController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\GoalController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\ControlPanelController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\ProposalController;

// Public routes
// Minimal named login route to satisfy Redirect behavior in auth middleware
Route::get('/login', function () { return response()->json(['message' => 'login'], 401); })->name('login');

Route::post('/login', [AuthController::class, 'login']);
Route::post('/auth/google', [AuthController::class, 'googleLogin'])->name('auth.google');
Route::post('/register-request', [AuthController::class, 'requestRegistration']);

// Token-friendly download routes (accept ?token= or Authorization: Bearer <token>)
Route::get('/payments/export/csv', [PaymentController::class, 'exportCsv'])->middleware(\App\Http\Middleware\AuthenticateDownload::class);
Route::get('/payments/export/pdf', [PaymentController::class, 'exportPdf'])->middleware(\App\Http\Middleware\AuthenticateDownload::class);
Route::get('/members/{id}/passbook/pdf', [WalletController::class, 'passpdf'])->middleware(\App\Http\Middleware\AuthenticateDownload::class);
Route::get('/documents/{id}/download', [DocumentController::class, 'download'])->middleware(\App\Http\Middleware\AuthenticateDownload::class);

Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/me/password', [AuthController::class, 'changePassword']);

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Members (read)
    Route::get('/members', [MemberController::class, 'index']);
    Route::get('/members/{id}', [MemberController::class, 'show']);
    Route::post('/members/{id}/link-google', [MemberController::class, 'linkGoogle']);
    Route::get('/members/{id}/wallet', [WalletController::class, 'show']);
    Route::get('/members/{id}/passbook', [WalletController::class, 'passbook']);

    // Transactions (read)
    Route::get('/transactions', [TransactionController::class, 'index']);

    // Payments (read)
    Route::get('/payments', [PaymentController::class, 'index']);

    // Projects (read)
    Route::get('/projects', [ProjectController::class, 'index']);
    Route::get('/projects/{id}', [ProjectController::class, 'show']);
    Route::get('/projects/{id}/collections', [CollectionController::class, 'index']);
    Route::get('/projects/{id}/milestones', [ProjectController::class, 'milestones']);

    // Expenses (read)
    Route::get('/expenses', [ExpenseController::class, 'index']);

    // Notices
    Route::get('/notices', [NoticeController::class, 'index']);

    // Proposals
    Route::get('/proposals', [ProposalController::class, 'index']);
    Route::post('/proposals', [ProposalController::class, 'store']);
    Route::post('/proposals/{id}/vote', [ProposalController::class, 'vote']);

    // Documents
    Route::get('/documents', [DocumentController::class, 'index']);

    // Goals
    Route::get('/goals', [GoalController::class, 'index']);
    Route::post('/goals', [GoalController::class, 'store']);
    Route::put('/goals/{id}', [GoalController::class, 'update']);
    Route::delete('/goals/{id}', [GoalController::class, 'destroy']);

    // Activity Log
    Route::get('/activity-log', [ActivityLogController::class, 'index']);
    Route::post('/activity-log', [ActivityLogController::class, 'store']);

    // About
    Route::get('/about', [MemberController::class, 'about']);

    // ─── Finance + Admin ─────────────────────────────────
    Route::middleware('role:admin,finance')->group(function () {
        Route::post('/transactions', [TransactionController::class, 'store']);
        Route::put('/transactions/{id}', [TransactionController::class, 'update']);
        Route::delete('/transactions/{id}', [TransactionController::class, 'destroy']);

        Route::post('/payments', [PaymentController::class, 'store']);
        Route::delete('/payments/{id}', [PaymentController::class, 'destroy']);
        Route::get('/payments/whatsapp', [PaymentController::class, 'whatsappText']);
        Route::get('/payments/whatsapp', [PaymentController::class, 'whatsappText']);

        Route::post('/expenses', [ExpenseController::class, 'store']);
        Route::put('/expenses/{id}', [ExpenseController::class, 'update']);
        Route::delete('/expenses/{id}', [ExpenseController::class, 'destroy']);
    });

    // ─── Secretary + Admin ───────────────────────────────
    Route::middleware('role:admin,secretary')->group(function () {
        Route::post('/notices', [NoticeController::class, 'store']);
        Route::delete('/notices/{id}', [NoticeController::class, 'destroy']);
        Route::post('/documents', [DocumentController::class, 'store']);
        Route::delete('/documents/{id}', [DocumentController::class, 'destroy']);
    });

    // ─── Admin Only ──────────────────────────────────────
    Route::middleware('role:admin')->group(function () {
        // Control panel
        Route::get('/control-panel', [ControlPanelController::class, 'index']);
        Route::get('/pending-registrations', [ControlPanelController::class, 'pendingRegistrations']);
        Route::post('/registrations/{id}/approve', [ControlPanelController::class, 'approve']);
        Route::post('/registrations/{id}/reject', [ControlPanelController::class, 'reject']);

        // Member management
        Route::post('/members', [MemberController::class, 'store']);
        Route::put('/members/{id}', [MemberController::class, 'update']);
        Route::post('/members/reset', [MemberController::class, 'reset']);
        Route::post('/members/{id}/unlink-google', [MemberController::class, 'unlinkGoogle']);

        // Project management
        Route::post('/projects', [ProjectController::class, 'store']);
        Route::put('/projects/{id}', [ProjectController::class, 'update']);
        Route::delete('/projects/{id}', [ProjectController::class, 'destroy']);
        Route::post('/projects/{id}/collections', [CollectionController::class, 'store']);
        // Generic collections endpoint (accepts project_id in payload)
        Route::post('/collections', [CollectionController::class, 'storeGeneric']);
        Route::post('/projects/{id}/milestones', [ProjectController::class, 'storeMilestone']);
        Route::put('/milestones/{id}', [ProjectController::class, 'updateMilestone']);
        // Proposals admin actions
        Route::put('/proposals/{id}', [ProposalController::class, 'update']);
    });
});
