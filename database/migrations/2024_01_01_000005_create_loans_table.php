<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('member_id');
            $table->string('loan_number', 50)->unique();
            $table->decimal('amount', 12, 2);
            $table->decimal('interest_rate', 5, 2);
            $table->enum('interest_type', ['flat', 'reducing', 'compound'])->default('flat');
            $table->decimal('total_interest', 12, 2);
            $table->decimal('total_repayment', 12, 2);
            $table->text('purpose');
            $table->integer('repayment_period');
            $table->enum('repayment_frequency', ['weekly', 'bi-weekly', 'monthly'])->default('monthly');
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('monthly_repayment', 12, 2);
            $table->text('collateral')->nullable();
            $table->string('guarantor_name');
            $table->string('guarantor_phone', 20);
            $table->text('guarantor_address')->nullable();
            $table->unsignedBigInteger('guarantor_member_id')->nullable();
            $table->enum('status', ['pending', 'secretary_approved', 'chairman_approved', 'approved', 'rejected', 'disbursed', 'active', 'completed', 'defaulted'])->default('pending');
            $table->date('disbursement_date')->nullable();
            $table->string('disbursement_method', 50)->nullable();
            $table->string('disbursement_reference', 100)->nullable();
            $table->timestamp('secretary_approved_at')->nullable();
            $table->unsignedBigInteger('secretary_approved_by')->nullable();
            $table->timestamp('chairman_approved_at')->nullable();
            $table->unsignedBigInteger('chairman_approved_by')->nullable();
            $table->timestamp('treasurer_approved_at')->nullable();
            $table->unsignedBigInteger('treasurer_approved_by')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->foreign('member_id')->references('id')->on('members')->onDelete('cascade');
            $table->foreign('guarantor_member_id')->references('id')->on('members')->onDelete('set null');
            $table->foreign('secretary_approved_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('chairman_approved_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('treasurer_approved_by')->references('id')->on('users')->onDelete('set null');
            $table->index('member_id');
            $table->index('loan_number');
            $table->index('status');
            $table->index('start_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};