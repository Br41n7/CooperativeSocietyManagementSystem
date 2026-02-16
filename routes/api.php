<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MemberController;
use App\Http\Controllers\Api\SavingsController;
use App\Http\Controllers\Api\LoanController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\MeetingController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    Route::get('/member/dashboard', [MemberController::class, 'dashboard']);
    Route::get('/member/profile', [MemberController::class, 'profile']);
    Route::put('/member/profile', [MemberController::class, 'updateProfile']);
    Route::get('/member/savings', [SavingsController::class, 'history']);
    Route::get('/member/loans', [LoanController::class, 'index']);

    Route::post('/savings/contribute', [SavingsController::class, 'contribute']);
    Route::get('/savings/wallet', [SavingsController::class, 'wallet']);
    Route::get('/savings/history', [SavingsController::class, 'history']);

    Route::post('/loans/apply', [LoanController::class, 'apply']);
    Route::get('/loans', [LoanController::class, 'index']);
    Route::get('/loans/{id}', [LoanController::class, 'show']);
    Route::get('/loans/{id}/schedule', [LoanController::class, 'schedule']);
    Route::post('/loans/{id}/repay', [LoanController::class, 'repay']);

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::put('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::put('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);

    Route::middleware('role:super_admin,chairman,secretary,treasurer')->group(function () {
        Route::get('/admin/dashboard', [AdminController::class, 'dashboard']);
        Route::get('/admin/pending-approvals', [AdminController::class, 'pendingApprovals']);
        Route::get('/admin/system-health', [AdminController::class, 'systemHealth']);

        Route::get('/members', [MemberController::class, 'index']);
        Route::get('/members/{id}', [MemberController::class, 'show']);
        Route::put('/members/{id}/approve', [MemberController::class, 'approve']);
        Route::put('/members/{id}/suspend', [MemberController::class, 'suspend']);
        Route::put('/members/{id}/reactivate', [MemberController::class, 'reactivate']);

        Route::get('/savings', [SavingsController::class, 'all']);
        Route::get('/savings/{id}', [SavingsController::class, 'show']);
        Route::put('/savings/{id}/adjust', [SavingsController::class, 'adjust']);
        Route::get('/savings/summary', [SavingsController::class, 'summary']);

        Route::put('/loans/{id}/approve', [LoanController::class, 'approve']);
        Route::put('/loans/{id}/reject', [LoanController::class, 'reject']);
        Route::post('/loans/{id}/disburse', [LoanController::class, 'disburse']);

        Route::post('/documents', [DocumentController::class, 'store']);
        Route::get('/documents', [DocumentController::class, 'index']);
        Route::get('/documents/{id}', [DocumentController::class, 'show']);
        Route::delete('/documents/{id}', [DocumentController::class, 'destroy']);
        Route::get('/documents/{id}/download', [DocumentController::class, 'download']);

        Route::post('/meetings', [MeetingController::class, 'store']);
        Route::get('/meetings', [MeetingController::class, 'index']);
        Route::get('/meetings/{id}', [MeetingController::class, 'show']);
        Route::put('/meetings/{id}', [MeetingController::class, 'update']);
        Route::delete('/meetings/{id}', [MeetingController::class, 'destroy']);
        Route::post('/meetings/{id}/attend', [MeetingController::class, 'markAttendance']);
        Route::post('/meetings/{id}/votes', [MeetingController::class, 'createVote']);
        Route::post('/meetings/{id}/votes/{voteId}/cast', [MeetingController::class, 'castVote']);

        Route::get('/reports/financial', [ReportController::class, 'financialSummary']);
        Route::get('/reports/members', [ReportController::class, 'memberReport']);
        Route::get('/reports/loans', [ReportController::class, 'loanReport']);
        Route::get('/reports/savings', [ReportController::class, 'savingsReport']);
        Route::get('/reports/activity', [ReportController::class, 'activityLog']);
        Route::get('/reports/analytics', [ReportController::class, 'analytics']);

        Route::get('/activity-log', [AdminController::class, 'activityLog']);
    });

    Route::middleware('role:super_admin')->group(function () {
        Route::delete('/members/{id}', [MemberController::class, 'destroy']);
    });
});