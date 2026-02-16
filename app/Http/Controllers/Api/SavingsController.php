<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Savings;
use App\Models\Transaction;
use App\Models\Member;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class SavingsController extends Controller
{
    public function contribute(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:100',
            'contribution_type' => 'required|in:monthly,voluntary,fixed,penalty,refund',
            'payment_method' => 'required|in:cash,bank_transfer,online,deduction',
            'payment_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $member = $request->user()->member;

        if (!$member || $member->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Only active members can make contributions'
            ], 403);
        }

        DB::beginTransaction();

        try {
            $savings = Savings::create([
                'member_id' => $member->id,
                'transaction_number' => Savings::generateTransactionNumber(),
                'amount' => $request->amount,
                'contribution_type' => $request->contribution_type,
                'payment_method' => $request->payment_method,
                'payment_date' => $request->payment_date,
                'month' => date('m', strtotime($request->payment_date)),
                'year' => date('Y', strtotime($request->payment_date)),
                'notes' => $request->notes,
                'created_by' => $request->user()->id,
            ]);

            $member->total_savings += $request->amount;
            $member->last_contribution_date = $request->payment_date;
            $member->save();

            $transaction = Transaction::create([
                'transaction_number' => Transaction::generateTransactionNumber(),
                'transaction_type' => 'savings',
                'reference_id' => $savings->id,
                'reference_type' => Savings::class,
                'member_id' => $member->id,
                'amount' => $request->amount,
                'description' => "Savings contribution - {$request->contribution_type}",
                'transaction_date' => now(),
                'created_by' => $request->user()->id,
            ]);

            ActivityLog::log(
                $request->user()->id,
                'savings_contribution',
                "Savings contribution of ₦" . number_format($request->amount) . " by {$member->member_number}",
                $savings
            );

            Notification::createContributionConfirmation(
                $request->user()->id,
                $request->amount,
                $savings->transaction_number
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Contribution recorded successfully',
                'data' => [
                    'savings' => $savings,
                    'transaction' => $transaction,
                    'new_balance' => $member->total_savings,
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to record contribution',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function wallet(Request $request)
    {
        $member = $request->user()->member;

        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => 'Member profile not found'
            ], 404);
        }

        $savings = $member->savings()
            ->orderBy('payment_date', 'desc')
            ->get();

        $monthlyContributions = $member->savings()
            ->where('contribution_type', 'monthly')
            ->selectRaw('SUM(amount) as total, month, year')
            ->groupBy('month', 'year')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'balance' => $member->total_savings,
                'total_contributions' => $savings->count(),
                'total_amount' => $savings->sum('amount'),
                'monthly_contributions' => $monthlyContributions,
                'recent_savings' => $savings->take(10),
            ]
        ]);
    }

    public function history(Request $request)
    {
        $member = $request->user()->member;

        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => 'Member profile not found'
            ], 404);
        }

        $query = $member->savings();

        if ($request->contribution_type) {
            $query->where('contribution_type', $request->contribution_type);
        }

        if ($request->from_date && $request->to_date) {
            $query->whereBetween('payment_date', [$request->from_date, $request->to_date]);
        }

        if ($request->month && $request->year) {
            $query->where('month', $request->month)->where('year', $request->year);
        }

        $savings = $query->orderBy('payment_date', 'desc')
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $savings
        ]);
    }

    public function all(Request $request)
    {
        $user = $request->user();

        if (!$user->hasPermission('savings.view')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $query = Savings::with('member', 'createdBy');

        if ($request->search) {
            $query->whereHas('member', function ($q) use ($request) {
                $q->where('member_number', 'like', '%' . $request->search . '%')
                  ->orWhere('first_name', 'like', '%' . $request->search . '%')
                  ->orWhere('last_name', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->contribution_type) {
            $query->where('contribution_type', $request->contribution_type);
        }

        if ($request->from_date && $request->to_date) {
            $query->whereBetween('payment_date', [$request->from_date, $request->to_date]);
        }

        $savings = $query->orderBy('payment_date', 'desc')
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $savings
        ]);
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();

        if (!$user->hasPermission('savings.view')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $savings = Savings::with('member', 'createdBy', 'originalSavings')
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $savings
        ]);
    }

    public function adjust(Request $request, $id)
    {
        $user = $request->user();

        if (!$user->hasPermission('savings.edit')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0',
            'reason' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $originalSavings = Savings::findOrFail($id);

        DB::beginTransaction();

        try {
            $difference = $request->amount - $originalSavings->amount;

            $newSavings = Savings::create([
                'member_id' => $originalSavings->member_id,
                'transaction_number' => Savings::generateTransactionNumber(),
                'amount' => $request->amount,
                'contribution_type' => $originalSavings->contribution_type,
                'payment_method' => $originalSavings->payment_method,
                'payment_date' => $originalSavings->payment_date,
                'month' => $originalSavings->month,
                'year' => $originalSavings->year,
                'notes' => "Adjustment: {$request->reason}",
                'is_adjusted' => true,
                'original_savings_id' => $originalSavings->id,
                'created_by' => $user->id,
            ]);

            $member = $originalSavings->member;
            $member->total_savings += $difference;
            $member->save();

            $originalSavings->is_adjusted = true;
            $originalSavings->save();

            $transaction = Transaction::create([
                'transaction_number' => Transaction::generateTransactionNumber(),
                'transaction_type' => $difference > 0 ? 'savings' : 'refund',
                'reference_id' => $newSavings->id,
                'reference_type' => Savings::class,
                'member_id' => $member->id,
                'amount' => abs($difference),
                'description' => "Savings adjustment: {$request->reason}",
                'transaction_date' => now(),
                'created_by' => $user->id,
            ]);

            ActivityLog::log(
                $user->id,
                'savings_adjustment',
                "Savings adjustment from ₦" . number_format($originalSavings->amount) . 
                " to ₦" . number_format($request->amount) . " for {$member->member_number}",
                $newSavings,
                ['original_amount' => $originalSavings->amount],
                ['new_amount' => $request->amount]
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Savings adjusted successfully',
                'data' => [
                    'original_savings' => $originalSavings,
                    'new_savings' => $newSavings,
                    'difference' => $difference,
                    'new_balance' => $member->total_savings,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to adjust savings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function summary(Request $request)
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

        $totalSavings = $query->sum('amount');
        $totalContributions = $query->count();
        $activeContributors = $query->distinct('member_id')->count();

        $byType = Savings::selectRaw('contribution_type, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('contribution_type')
            ->get();

        $byMonth = Savings::selectRaw('year, month, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('year', 'month')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->take(12)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'total_savings' => $totalSavings,
                'total_contributions' => $totalContributions,
                'active_contributors' => $activeContributors,
                'by_type' => $byType,
                'by_month' => $byMonth,
            ]
        ]);
    }
}