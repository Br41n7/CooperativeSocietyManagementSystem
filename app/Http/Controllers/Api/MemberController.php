<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MemberController extends Controller
{
    public function dashboard(Request $request)
    {
        $member = $request->user()->member;

        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => 'Member profile not found'
            ], 404);
        }

        $activeLoan = $member->activeLoans()->first();
        $nextPayment = $activeLoan ? $activeLoan->next_payment : null;
        
        $recentSavings = $member->savings()
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        $recentLoans = $member->loans()
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        $recentTransactions = $member->transactions()
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        $unreadNotifications = $request->user()
            ->notifications()
            ->unread()
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'member' => $member,
                'savings_balance' => $member->savings_balance,
                'outstanding_loan_balance' => $member->outstanding_loan_balance,
                'loan_eligibility' => $member->loan_eligibility,
                'credit_score' => $member->credit_score,
                'default_risk' => $member->default_risk,
                'active_loan' => $activeLoan,
                'next_payment' => $nextPayment,
                'recent_savings' => $recentSavings,
                'recent_loans' => $recentLoans,
                'recent_transactions' => $recentTransactions,
                'unread_notifications' => $unreadNotifications,
            ]
        ]);
    }

    public function profile(Request $request)
    {
        $member = $request->user()->member;

        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => 'Member profile not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $member
        ]);
    }

    public function updateProfile(Request $request)
    {
        $member = $request->user()->member;

        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => 'Member profile not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'occupation' => 'nullable|string|max:100',
            'employer' => 'nullable|string|max:200',
            'monthly_income' => 'nullable|numeric|min:0',
            'next_of_kin_name' => 'nullable|string|max:200',
            'next_of_kin_phone' => 'nullable|string|max:20',
            'next_of_kin_relationship' => 'nullable|string|max:100',
            'next_of_kin_address' => 'nullable|string',
            'bank_name' => 'nullable|string|max:100',
            'bank_account_number' => 'nullable|string|max:50',
            'bank_account_name' => 'nullable|string|max:200',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $oldValues = $member->toArray();
        $member->update($request->all());
        $newValues = $member->toArray();

        ActivityLog::log(
            $request->user()->id,
            'profile_update',
            'Member profile updated',
            $member,
            $oldValues,
            $newValues
        );

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => $member
        ]);
    }

    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user->hasPermission('members.view')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $query = Member::query();

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('first_name', 'like', '%' . $request->search . '%')
                  ->orWhere('last_name', 'like', '%' . $request->search . '%')
                  ->orWhere('member_number', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->is_defaulting) {
            $query->where('is_defaulting', $request->is_defaulting);
        }

        $members = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $members
        ]);
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();

        if (!$user->hasPermission('members.view')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $member = Member::with([
            'savings',
            'loans',
            'loans.repayments',
            'transactions',
            'meetingAttendance',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'member' => $member,
                'savings_balance' => $member->savings_balance,
                'outstanding_loan_balance' => $member->outstanding_loan_balance,
                'loan_eligibility' => $member->loan_eligibility,
                'default_risk' => $member->default_risk,
            ]
        ]);
    }

    public function approve(Request $request, $id)
    {
        $user = $request->user();

        if (!$user->hasPermission('members.approve')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $member = Member::findOrFail($id);

        if ($member->status === 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Member is already active'
            ], 400);
        }

        $oldStatus = $member->status;
        $member->status = 'active';
        $member->membership_date = now()->format('Y-m-d');
        $member->save();

        $member->user()->update(['is_active' => true]);

        ActivityLog::log(
            $user->id,
            'member_approval',
            "Member {$member->member_number} approved",
            $member,
            ['status' => $oldStatus],
            ['status' => 'active']
        );

        return response()->json([
            'success' => true,
            'message' => 'Member approved successfully',
            'data' => $member
        ]);
    }

    public function suspend(Request $request, $id)
    {
        $user = $request->user();

        if (!$user->hasPermission('members.edit')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $member = Member::findOrFail($id);
        $oldStatus = $member->status;
        $member->status = 'suspended';
        $member->save();

        $member->user()->update(['is_active' => false]);

        ActivityLog::log(
            $user->id,
            'member_suspension',
            "Member {$member->member_number} suspended",
            $member,
            ['status' => $oldStatus],
            ['status' => 'suspended']
        );

        return response()->json([
            'success' => true,
            'message' => 'Member suspended successfully',
            'data' => $member
        ]);
    }

    public function reactivate(Request $request, $id)
    {
        $user = $request->user();

        if (!$user->hasPermission('members.edit')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $member = Member::findOrFail($id);
        $oldStatus = $member->status;
        $member->status = 'active';
        $member->is_defaulting = false;
        $member->save();

        $member->user()->update(['is_active' => true]);

        ActivityLog::log(
            $user->id,
            'member_reactivation',
            "Member {$member->member_number} reactivated",
            $member,
            ['status' => $oldStatus, 'is_defaulting' => true],
            ['status' => 'active', 'is_defaulting' => false]
        );

        return response()->json([
            'success' => true,
            'message' => 'Member reactivated successfully',
            'data' => $member
        ]);
    }
}