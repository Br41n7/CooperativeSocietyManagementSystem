<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\Loan;
use App\Models\Savings;
use App\Models\Transaction;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function financialSummary(Request $request)
    {
        $user = $request->user();

        if (!$user->hasPermission('reports.view')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $fromDate = $request->from_date ?? now()->startOfMonth()->format('Y-m-d');
        $toDate = $request->to_date ?? now()->endOfMonth()->format('Y-m-d');

        $savingsIn = Savings::whereBetween('payment_date', [$fromDate, $toDate])
            ->sum('amount');

        $savingsOut = Transaction::where('transaction_type', 'withdrawal')
            ->whereBetween('transaction_date', [$fromDate, $toDate])
            ->sum('amount');

        $loanDisbursements = Transaction::where('transaction_type', 'loan_disbursement')
            ->whereBetween('transaction_date', [$fromDate, $toDate])
            ->sum('amount');

        $loanRepayments = Transaction::where('transaction_type', 'loan_repayment')
            ->whereBetween('transaction_date', [$fromDate, $toDate])
            ->sum('amount');

        $interestEarned = LoanRepayment::whereBetween('payment_date', [$fromDate, $toDate])
            ->sum('interest_amount');

        $penalties = LoanRepayment::whereBetween('payment_date', [$fromDate, $toDate])
            ->sum('penalty_amount');

        return response()->json([
            'success' => true,
            'data' => [
                'period' => [
                    'from' => $fromDate,
                    'to' => $toDate,
                ],
                'income' => [
                    'savings_in' => $savingsIn,
                    'loan_repayments' => $loanRepayments,
                    'interest_earned' => $interestEarned,
                    'penalties' => $penalties,
                    'total' => $savingsIn + $loanRepayments + $interestEarned + $penalties,
                ],
                'expenses' => [
                    'savings_out' => $savingsOut,
                    'loan_disbursements' => $loanDisbursements,
                    'total' => $savingsOut + $loanDisbursements,
                ],
                'net_income' => ($savingsIn + $loanRepayments + $interestEarned + $penalties) - ($savingsOut + $loanDisbursements),
            ]
        ]);
    }

    public function memberReport(Request $request)
    {
        $user = $request->user();

        if (!$user->hasPermission('reports.view')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $query = Member::query();

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $members = $query->get();

        $totalSavings = $members->sum('total_savings');
        $totalLoansTaken = $members->sum('total_loans_taken');
        $totalLoansRepaid = $members->sum('total_loans_repaid');
        $averageCreditScore = $members->avg('credit_score');

        $byStatus = $members->groupBy('status')->map(function ($group) {
            return [
                'count' => $group->count(),
                'total_savings' => $group->sum('total_savings'),
                'total_loans' => $group->sum('total_loans_taken'),
            ];
        });

        $topSavers = $members->sortByDesc('total_savings')->take(10);
        $topBorrowers = $members->sortByDesc('total_loans_taken')->take(10);

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => [
                    'total_members' => $members->count(),
                    'total_savings' => $totalSavings,
                    'total_loans_taken' => $totalLoansTaken,
                    'total_loans_repaid' => $totalLoansRepaid,
                    'outstanding_loans' => $totalLoansTaken - $totalLoansRepaid,
                    'average_credit_score' => round($averageCreditScore, 2),
                ],
                'by_status' => $byStatus,
                'top_savers' => $topSavers,
                'top_borrowers' => $topBorrowers,
            ]
        ]);
    }

    public function loanReport(Request $request)
    {
        $user = $request->user();

        if (!$user->hasPermission('reports.view')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $query = Loan::query();

        if ($request->from_date && $request->to_date) {
            $query->whereBetween('created_at', [$request->from_date, $request->to_date]);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $loans = $query->get();

        $totalAmount = $loans->sum('amount');
        $totalInterest = $loans->sum('total_interest');
        $totalRepayment = $loans->sum('total_repayment');
        $totalRepaid = $loans->sum(function ($loan) {
            return $loan->total_repaid;
        });

        $outstandingBalance = $totalRepayment - $totalRepaid;

        $byStatus = $loans->groupBy('status')->map(function ($group) {
            return [
                'count' => $group->count(),
                'total_amount' => $group->sum('amount'),
                'total_repayment' => $group->sum('total_repayment'),
            ];
        });

        $byPurpose = $loans->groupBy('purpose')->map(function ($group) {
            return [
                'count' => $group->count(),
                'total_amount' => $group->sum('amount'),
            ];
        })->sortByDesc('total_amount')->take(10);

        $overdueLoans = Loan::whereHas('repayments', function ($q) {
            $q->where('status', 'overdue');
        })->with(['member', 'repayments' => function ($q) {
            $q->where('status', 'overdue');
        }])->get();

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => [
                    'total_loans' => $loans->count(),
                    'total_amount' => $totalAmount,
                    'total_interest' => $totalInterest,
                    'total_repayment' => $totalRepayment,
                    'total_repaid' => $totalRepaid,
                    'outstanding_balance' => $outstandingBalance,
                    'repayment_rate' => $totalRepayment > 0 ? round(($totalRepaid / $totalRepayment) * 100, 2) : 0,
                ],
                'by_status' => $byStatus,
                'by_purpose' => $byPurpose,
                'overdue_loans' => $overdueLoans->map(function ($loan) {
                    return [
                        'loan' => $loan,
                        'member' => $loan->member,
                        'overdue_amount' => $loan->overdue_amount,
                        'overdue_installments' => $loan->repayments->where('status', 'overdue')->count(),
                    ];
                }),
            ]
        ]);
    }

    public function savingsReport(Request $request)
    {
        $user = $request->user();

        if (!$user->hasPermission('reports.view')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $query = Savings::query();

        if ($request->from_date && $request->to_date) {
            $query->whereBetween('payment_date', [$request->from_date, $request->to_date]);
        }

        if ($request->contribution_type) {
            $query->where('contribution_type', $request->contribution_type);
        }

        $savings = $query->get();

        $totalAmount = $savings->sum('amount');
        $totalContributions = $savings->count();

        $byType = $savings->groupBy('contribution_type')->map(function ($group) {
            return [
                'count' => $group->count(),
                'total_amount' => $group->sum('amount'),
                'percentage' => $totalAmount > 0 ? round(($group->sum('amount') / $totalAmount) * 100, 2) : 0,
            ];
        });

        $byMethod = $savings->groupBy('payment_method')->map(function ($group) {
            return [
                'count' => $group->count(),
                'total_amount' => $group->sum('amount'),
            ];
        });

        $monthlyTrend = Savings::selectRaw('YEAR(payment_date) as year, MONTH(payment_date) as month, SUM(amount) as total, COUNT(*) as count')
            ->where('payment_date', '>=', now()->subMonths(12))
            ->groupBy('year', 'month')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get();

        $topContributors = Savings::selectRaw('member_id, SUM(amount) as total, COUNT(*) as count')
            ->with('member')
            ->groupBy('member_id')
            ->orderBy('total', 'desc')
            ->take(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => [
                    'total_amount' => $totalAmount,
                    'total_contributions' => $totalContributions,
                    'average_contribution' => $totalContributions > 0 ? round($totalAmount / $totalContributions, 2) : 0,
                ],
                'by_type' => $byType,
                'by_method' => $byMethod,
                'monthly_trend' => $monthlyTrend,
                'top_contributors' => $topContributors,
            ]
        ]);
    }

    public function activityLog(Request $request)
    {
        $user = $request->user();

        if (!$user->hasPermission('reports.view')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $query = ActivityLog::with('user', 'model');

        if ($request->user_id) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->action) {
            $query->where('action', $request->action);
        }

        if ($request->from_date && $request->to_date) {
            $query->whereBetween('created_at', [$request->from_date, $request->to_date]);
        }

        $activities = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 50);

        return response()->json([
            'success' => true,
            'data' => $activities
        ]);
    }

    public function analytics(Request $request)
    {
        $user = $request->user();

        if (!$user->hasPermission('reports.view')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $memberGrowth = Member::selectRaw('DATE(membership_date) as date, COUNT(*) as count')
            ->where('membership_date', '>=', now()->subMonths(12))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $savingsGrowth = Savings::selectRaw('DATE(payment_date) as date, SUM(amount) as total')
            ->where('payment_date', '>=', now()->subMonths(12))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $loanApprovalRate = Loan::selectRaw('status, COUNT(*) as count')
            ->where('created_at', '>=', now()->subMonths(12))
            ->groupBy('status')
            ->get();

        $repaymentPerformance = Loan::where('status', 'completed')
            ->selectRaw('DATEDIFF(end_date, start_date) as duration, total_repayment')
            ->get();

        $avgLoanDuration = $repaymentPerformance->avg('duration');
        $defaultRate = Loan::where('status', 'defaulted')->count() / max(Loan::count(), 1) * 100;

        return response()->json([
            'success' => true,
            'data' => [
                'member_growth' => $memberGrowth,
                'savings_growth' => $savingsGrowth,
                'loan_approval_rate' => $loanApprovalRate,
                'repayment_performance' => [
                    'average_duration_days' => round($avgLoanDuration ?? 0, 2),
                    'default_rate_percentage' => round($defaultRate, 2),
                ],
            ]
        ]);
    }
}