<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->enum('type', ['loan_approval', 'loan_rejection', 'contribution', 'repayment_reminder', 'meeting', 'announcement', 'system']);
            $table->string('title');
            $table->text('message');
            $table->json('data')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->boolean('sent_email')->default(false);
            $table->boolean('sent_sms')->default(false);
            $table->timestamp('email_sent_at')->nullable();
            $table->timestamp('sms_sent_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index('user_id');
            $table->index('type');
            $table->index('is_read');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};