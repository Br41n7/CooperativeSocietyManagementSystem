<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_repayments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('loan_id');
            $table->integer('installment_number');
            $table->date('due_date');
            $table->decimal('due_amount', 12, 2);
            $table->decimal('principal_amount', 12, 2);
            $table->decimal('interest_amount', 12, 2);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->date('payment_date')->nullable();
            $table->enum('payment_method', ['cash', 'bank_transfer', 'online', 'savings_deduction'])->nullable();
            $table->string('transaction_number', 50)->nullable();
            $table->string('receipt_number', 50)->nullable();
            $table->enum('status', ['pending', 'paid', 'partial', 'overdue', 'defaulted'])->default('pending');
            $table->integer('days_overdue')->default(0);
            $table->decimal('penalty_amount', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('loan_id')->references('id')->on('loans')->onDelete('cascade');
            $table->index('loan_id');
            $table->index('due_date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_repayments');
    }
};