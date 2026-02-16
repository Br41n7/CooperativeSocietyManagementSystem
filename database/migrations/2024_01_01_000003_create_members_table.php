<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->string('member_number', 20)->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->date('date_of_birth');
            $table->enum('gender', ['male', 'female', 'other']);
            $table->string('phone', 20);
            $table->text('address');
            $table->string('city');
            $table->string('state');
            $table->string('postal_code', 20)->nullable();
            $table->string('country', 100)->default('Nigeria');
            $table->string('occupation', 100)->nullable();
            $table->string('employer', 200)->nullable();
            $table->decimal('monthly_income', 12, 2)->nullable();
            $table->string('next_of_kin_name');
            $table->string('next_of_kin_phone', 20);
            $table->string('next_of_kin_relationship');
            $table->text('next_of_kin_address')->nullable();
            $table->string('bank_name', 100)->nullable();
            $table->string('bank_account_number', 50)->nullable();
            $table->string('bank_account_name', 200)->nullable();
            $table->string('profile_photo', 255)->nullable();
            $table->string('id_document', 255)->nullable();
            $table->string('signature', 255)->nullable();
            $table->enum('status', ['pending', 'active', 'inactive', 'suspended', 'defaulting'])->default('pending');
            $table->date('membership_date')->nullable();
            $table->date('last_contribution_date')->nullable();
            $table->boolean('is_defaulting')->default(false);
            $table->integer('credit_score')->default(100);
            $table->decimal('total_savings', 12, 2)->default(0);
            $table->decimal('total_loans_taken', 12, 2)->default(0);
            $table->decimal('total_loans_repaid', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('member_number');
            $table->index('status');
            $table->index('is_defaulting');
            $table->index('membership_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};