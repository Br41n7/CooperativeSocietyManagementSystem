<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\Loan;
use App\Models\Savings;
use App\Models\Transaction;
use App\Models\Meeting;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function dashboard(Request $request)
    {
        $user = $request->user();

        if (!$user->hasPermission('reports.view')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $totalMembers = Member::where('status', 'active')->count();
        $pendingMembers = Member::where('status', 'pending')->count();
        $defaultingMembers = Member::where('is_defaulting', true)->count();

        $totalSavings = Savings::sum('amount');
        $savingsThisMonth = Savings::whereMonth('payment_date', now()->month)
            ->whereYear('payment_date', now()->year)
            ->sum('amount');

        $totalLoans = Loan::where('status', 'active')->count();
        $activeLoansAmount = Loan::where('status', 'active')->sum('amount');
        $pendingLoans = Loan::where('status', 'pending')->count();
        $approvedLoans = Loan::where('status', 'approved')->count();

        $totalOutstanding = Loan::where('status', 'active')
            ->get()
            ->sum(function ($loan) {
                return $loan->outstanding_balance;
            });

        $totalRepaid = Loan::where('status', 'completed')->sum('total_repayment');
        $repaidThisMonth = Transaction::where('transaction_type', 'loan_repayment')
            ->whereMonth('transaction_date', now()->month)
            ->whereYear('transaction_date', now()->year)
            ->sum('amount');

        $upcomingMeetings = Meeting::where('meeting_date', '>', now())
            ->orderBy('meeting_date')
            ->take(5)
            ->get();

        $recentActivity = ActivityLog::with('user', 'model')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        $monthlySavings = Savings::selectRaw('YEAR(payment_date) as year, MONTH(payment_date) as month, SUM(amount) as total')
            ->where('payment_date', '>=', now()->subMonths(12))
            ->groupBy('year', 'month')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get();

        $monthlyLoans = Loan::selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, SUM(amount) as total, COUNT(*) as count')
            ->where('created_at', '>=', now()->subMonths(12))
            ->groupBy('year', 'month')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get();

        $loanApplicationsByStatus = Loan::selectRaw('status, COUNT(*) as count, SUM(amount) as total')
            ->groupBy('status')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'members' => [
                    'total' => $totalMembers,
                    'pending' => $pendingMembers,
                    'defaulting' => $defaultingMembers,
                ],
                'savings' => [
                    'total' => $totalSavings,
                    'this_month' => $savingsThisMonth,
                ],
                'loans' => [
                    'total' => $totalLoans,
                    'active_amount' => $activeLoansAmount,
                    'pending' => $pendingLoans,
                    'approved' => $approvedLoans,
                    'outstanding' => $totalOutstanding,
                ],
                'repayments' => [
                    'total_repaid' => $totalRepaid,
                    'this_month' => $repaidThisMonth,
                ],
                'financial_summary' => [
                    'total_assets' => $totalSavings + $totalRepaid,
                    'total_liabilities' => $totalOutstanding,
                    'net_worth' => ($totalSavings + $totalRepaid) - $totalOutstanding,
                ],
                'upcoming_meetings' => $upcomingMeetings,
                'recent_activity' => $recentActivity,
                'charts' => [
                    'monthly_savings' => $monthlySavings,
                    'monthly_loans' => $monthlyLoans,
                    'loan_status' => $loanApplicationsByStatus,
                ],
            ]
        ]);
    }

    public function pendingApprovals(Request $request)
    {
        $user = $request->user();

        if (!$user->hasPermission('members.approve') && !$user->hasPermission('loans.approve')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $pendingMembers = [];
        $pendingLoans = [];

        if ($user->hasPermission('members.approve')) {
            $pendingMembers = Member::where('status', 'pending')
                ->orderBy('created_at', 'desc')
                ->take(10)
                ->get();
        }

        if ($user->hasPermission('loans.approve')) {
            $pendingLoans = Loan::with('member')
                ->where('status', 'pending')
                ->orWhere('status', 'secretary_approved')
                ->orWhere('status', 'chairman_approved')
                ->orderBy('created_at', 'desc')
                ->take(10)
                ->get();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'pending_members' => $pendingMembers,
                'pending_loans' => $pendingLoans,
            ]
        ]);
    }

    public function systemHealth(Request $request)
    {
        $user = $request->user();

        if (!$user->hasRole('super_admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $overdueLoans = Loan::whereHas('repayments', function ($q) {
            $q->where('status', 'overdue');
        })->count();

        $defaultedLoans = Loan::where('status', 'defaulted')->count();

        $inactiveMembers = Member::where('status', 'inactive')
            ->where('last_contribution_date', '<', now()->subMonths(3))
            ->count();

        $recentActivities = ActivityLog::where('created_at', '>=', now()->subHours(24))
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'overdue_loans' => $overdueLoans,
                'defaulted_loans' => $defaultedLoans,
                'inactive_members' => $inactiveMembers,
                'recent_activities' => $recentActivities,
                'system_status' => 'healthy',
            ]
        ]);
    }
}