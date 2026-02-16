<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_number', 50)->unique();
            $table->enum('transaction_type', ['savings', 'loan_disbursement', 'loan_repayment', 'withdrawal', 'penalty', 'refund', 'expense', 'income']);
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('reference_type', 50)->nullable();
            $table->unsignedBigInteger('member_id')->nullable();
            $table->decimal('amount', 12, 2);
            $table->text('description');
            $table->timestamp('transaction_date');
            $table->unsignedBigInteger('created_by');
            $table->boolean('is_reversed')->default(false);
            $table->timestamp('reversed_at')->nullable();
            $table->unsignedBigInteger('reversed_by')->nullable();
            $table->text('reversal_reason')->nullable();
            $table->timestamps();

            $table->foreign('member_id')->references('id')->on('members')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('reversed_by')->references('id')->on('users')->onDelete('set null');
            $table->index('transaction_number');
            $table->index('transaction_type');
            $table->index('member_id');
            $table->index('transaction_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};