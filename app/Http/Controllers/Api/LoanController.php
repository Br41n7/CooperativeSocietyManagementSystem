<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Models\LoanRepayment;
use App\Models\Transaction;
use App\Models\Member;
use App\Models\ActivityLog;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class LoanController extends Controller
{
    public function apply(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:5000|max:500000',
            'purpose' => 'required|string|min:10',
            'repayment_period' => 'required|integer|min:3|max:36',
            'repayment_frequency' => 'required|in:weekly,bi-weekly,monthly',
            'interest_type' => 'required|in:flat,reducing,compound',
            'collateral' => 'nullable|string',
            'guarantor_name' => 'required|string|max:200',
            'guarantor_phone' => 'required|string|max:20',
            'guarantor_address' => 'nullable|string',
            'guarantor_member_id' => 'nullable|exists:members,id',
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
                'message' => 'Only active members can apply for loans'
            ], 403);
        }

        if ($member->is_defaulting) {
            return response()->json([
                'success' => false,
                'message' => 'You have defaulted loans. Please clear them first.'
            ], 403);
        }

        $outstandingBalance = $member->outstanding_loan_balance;
        if ($outstandingBalance > 0) {
            return response()->json([
                'success' => false,
                'message' => 'You have outstanding loan balance of ₦' . number_format($outstandingBalance)
            ], 400);
        }

        $loanEligibility = $member->loan_eligibility;
        if ($request->amount > $loanEligibility) {
            return response()->json([
                'success' => false,
                'message' => "Loan amount exceeds your eligibility of ₦" . number_format($loanEligibility)
            ], 400);
        }

        if ($request->guarantor_member_id) {
            $guarantor = Member::find($request->guarantor_member_id);
            if ($guarantor && $guarantor->id === $member->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot be your own guarantor'
                ], 400);
            }
        }

        DB::beginTransaction();

        try {
            $interestRate = 15; 
            $startDate = now()->addDays(7)->format('Y-m-d');

            $loan = Loan::create([
                'member_id' => $member->id,
                'loan_number' => Loan::generateLoanNumber(),
                'amount' => $request->amount,
                'interest_rate' => $interestRate,
                'interest_type' => $request->interest_type,
                'total_interest' => 0,
                'total_repayment' => 0,
                'purpose' => $request->purpose,
                'repayment_period' => $request->repayment_period,
                'repayment_frequency' => $request->repayment_frequency,
                'start_date' => $startDate,
                'end_date' => date('Y-m-d', strtotime($startDate . " + {$request->repayment_period} months")),
                'monthly_repayment' => 0,
                'collateral' => $request->collateral,
                'guarantor_name' => $request->guarantor_name,
                'guarantor_phone' => $request->guarantor_phone,
                'guarantor_address' => $request->guarantor_address,
                'guarantor_member_id' => $request->guarantor_member_id,
                'status' => 'pending',
            ]);

            $totalInterest = $loan->calculateInterest();
            $totalRepayment = $loan->amount + $totalInterest;
            $monthlyRepayment = $totalRepayment / $request->repayment_period;

            $loan->total_interest = $totalInterest;
            $loan->total_repayment = $totalRepayment;
            $loan->monthly_repayment = $monthlyRepayment;
            $loan->save();

            $loan->generateRepaymentSchedule();

            ActivityLog::log(
                $request->user()->id,
                'loan_application',
                "Loan application #{$loan->loan_number} for ₦" . number_format($request->amount),
                $loan
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Loan application submitted successfully',
                'data' => [
                    'loan' => $loan,
                    'repayment_schedule' => $loan->repayments,
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit loan application',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->hasRole('member')) {
            $member = $user->member;
            if (!$member) {
                return response()->json([
                    'success' => false,
                    'message' => 'Member profile not found'
                ], 404);
            }
            $query = $member->loans();
        } else {
            if (!$user->hasPermission('loans.view')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }
            $query = Loan::with('member');
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->search && !$user->hasRole('member')) {
            $query->where(function ($q) use ($request) {
                $q->where('loan_number', 'like', '%' . $request->search . '%')
                  ->orWhereHas('member', function ($mq) use ($request) {
                      $mq->where('first_name', 'like', '%' . $request->search . '%')
                        ->orWhere('last_name', 'like', '%' . $request->search . '%')
                        ->orWhere('member_number', 'like', '%' . $request->search . '%');
                  });
            });
        }

        $loans = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $loans
        ]);
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();

        if ($user->hasRole('member')) {
            $member = $user->member;
            $loan = Loan::with(['repayments', 'member', 'guarantor'])
                ->where('id', $id)
                ->where('member_id', $member->id)
                ->firstOrFail();
        } else {
            if (!$user->hasPermission('loans.view')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }
            $loan = Loan::with(['repayments', 'member', 'guarantor', 'secretaryApproval', 'chairmanApproval', 'treasurerApproval'])
                ->findOrFail($id);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'loan' => $loan,
                'repayment_schedule' => $loan->repayments,
                'total_repaid' => $loan->total_repaid,
                'outstanding_balance' => $loan->outstanding_balance,
                'completion_percentage' => $loan->completion_percentage,
            ]
        ]);
    }

    public function approve(Request $request, $id)
    {
        $user = $request->user();
        $role = $user->role->name;

        if (!in_array($role, ['secretary', 'chairman', 'treasurer'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $loan = Loan::findOrFail($id);

        if (!$loan->canBeApproved($role)) {
            return response()->json([
                'success' => false,
                'message' => 'This loan cannot be approved at this stage'
            ], 400);
        }

        $loan->approve($role, $user->id);

        ActivityLog::log(
            $user->id,
            'loan_approval',
            "Loan #{$loan->loan_number} approved by {$role}",
            $loan
        );

        if ($loan->status === 'approved') {
            Notification::createLoanApproval(
                $loan->member->user->id,
                $loan->loan_number,
                $loan->amount
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Loan approved successfully',
            'data' => $loan
        ]);
    }

    public function reject(Request $request, $id)
    {
        $user = $request->user();

        if (!$user->hasPermission('loans.reject')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|min:10',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $loan = Loan::findOrFail($id);

        if (!in_array($loan->status, ['pending', 'secretary_approved', 'chairman_approved'])) {
            return response()->json([
                'success' => false,
                'message' => 'This loan cannot be rejected at this stage'
            ], 400);
        }

        $loan->reject($request->reason, $user->id);

        ActivityLog::log(
            $user->id,
            'loan_rejection',
            "Loan #{$loan->loan_number} rejected. Reason: {$request->reason}",
            $loan
        );

        Notification::createLoanRejection(
            $loan->member->user->id,
            $loan->loan_number,
            $request->reason
        );

        return response()->json([
            'success' => true,
            'message' => 'Loan rejected successfully',
            'data' => $loan
        ]);
    }

    public function disburse(Request $request, $id)
    {
        $user = $request->user();

        if (!$user->hasPermission('loans.disburse')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'disbursement_date' => 'required|date',
            'disbursement_method' => 'required|string',
            'disbursement_reference' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $loan = Loan::findOrFail($id);

        if ($loan->status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Only approved loans can be disbursed'
            ], 400);
        }

        DB::beginTransaction();

        try {
            $loan->disburse(
                $request->disbursement_date,
                $request->disbursement_method,
                $request->disbursement_reference
            );

            $transaction = Transaction::create([
                'transaction_number' => Transaction::generateTransactionNumber(),
                'transaction_type' => 'loan_disbursement',
                'reference_id' => $loan->id,
                'reference_type' => Loan::class,
                'member_id' => $loan->member_id,
                'amount' => $loan->amount,
                'description' => "Loan disbursement - {$loan->loan_number}",
                'transaction_date' => now(),
                'created_by' => $user->id,
            ]);

            ActivityLog::log(
                $user->id,
                'loan_disbursement',
                "Loan #{$loan->loan_number} disbursed - ₦" . number_format($loan->amount),
                $loan
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Loan disbursed successfully',
                'data' => [
                    'loan' => $loan,
                    'transaction' => $transaction,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to disburse loan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function repay(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'repayment_id' => 'required|exists:loan_repayments,id',
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|in:cash,bank_transfer,online,savings_deduction',
            'payment_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $repayment = LoanRepayment::with('loan')->findOrFail($request->repayment_id);

        if ($repayment->loan_id != $id) {
            return response()->json([
                'success' => false,
                'message' => 'Repayment does not belong to this loan'
            ], 400);
        }

        $loan = $repayment->loan;

        if ($loan->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Only active loans can accept repayments'
            ], 400);
        }

        if ($request->amount > $repayment->remaining_amount) {
            return response()->json([
                'success' => false,
                'message' => 'Payment amount exceeds due amount'
            ], 400);
        }

        DB::beginTransaction();

        try {
            $repayment->paid_amount += $request->amount;
            $repayment->payment_date = $request->payment_date;
            $repayment->payment_method = $request->payment_method;

            if ($repayment->paid_amount >= ($repayment->due_amount + $repayment->penalty_amount)) {
                $repayment->status = 'paid';
                $repayment->receipt_number = LoanRepayment::generateReceiptNumber();
            } else {
                $repayment->status = 'partial';
            }

            $repayment->save();

            $transaction = Transaction::create([
                'transaction_number' => Transaction::generateTransactionNumber(),
                'transaction_type' => 'loan_repayment',
                'reference_id' => $repayment->id,
                'reference_type' => LoanRepayment::class,
                'member_id' => $loan->member_id,
                'amount' => $request->amount,
                'description' => "Loan repayment - {$loan->loan_number}, Installment {$repayment->installment_number}",
                'transaction_date' => now(),
                'created_by' => $request->user()->id,
            ]);

            if ($loan->outstanding_balance <= 0) {
                $loan->complete();
            }

            ActivityLog::log(
                $request->user()->id,
                'loan_repayment',
                "Loan repayment of ₦" . number_format($request->amount) . " for {$loan->loan_number}",
                $repayment
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment recorded successfully',
                'data' => [
                    'repayment' => $repayment,
                    'transaction' => $transaction,
                    'outstanding_balance' => $loan->outstanding_balance,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to record payment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function schedule(Request $request, $id)
    {
        $user = $request->user();
        $loan = Loan::with('repayments')->findOrFail($id);

        if ($user->hasRole('member')) {
            $member = $user->member;
            if ($loan->member_id !== $member->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }
        } else {
            if (!$user->hasPermission('loans.view')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'loan' => $loan,
                'schedule' => $loan->repayments->map(function ($repayment) {
                    $repayment->checkOverdue();
                    return $repayment;
                }),
                'summary' => [
                    'total_installments' => $loan->repayment_period,
                    'paid_installments' => $loan->repayments->where('status', 'paid')->count(),
                    'pending_installments' => $loan->repayments->where('status', 'pending')->count(),
                    'overdue_installments' => $loan->repayments->where('status', 'overdue')->count(),
                ]
            ]
        ]);
    }
}