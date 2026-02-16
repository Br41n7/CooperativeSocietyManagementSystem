<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('savings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('member_id');
            $table->string('transaction_number', 50)->unique();
            $table->decimal('amount', 12, 2);
            $table->enum('contribution_type', ['monthly', 'voluntary', 'fixed', 'penalty', 'refund']);
            $table->enum('payment_method', ['cash', 'bank_transfer', 'online', 'deduction']);
            $table->date('payment_date');
            $table->integer('month');
            $table->integer('year');
            $table->string('receipt_number', 50)->nullable();
            $table->boolean('receipt_generated')->default(false);
            $table->text('notes')->nullable();
            $table->boolean('is_adjusted')->default(false);
            $table->unsignedBigInteger('original_savings_id')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('member_id')->references('id')->on('members')->onDelete('cascade');
            $table->foreign('original_savings_id')->references('id')->on('savings')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users');
            $table->index('member_id');
            $table->index('transaction_number');
            $table->index('payment_date');
            $table->index('contribution_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('savings');
    }
};