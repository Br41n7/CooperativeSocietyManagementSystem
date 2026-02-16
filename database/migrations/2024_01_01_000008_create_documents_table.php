<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('document_type', ['constitution', 'meeting_minutes', 'loan_agreement', 'member_id', 'financial_report', 'policy', 'other']);
            $table->string('file_path', 500);
            $table->string('file_name');
            $table->bigInteger('file_size');
            $table->string('file_type');
            $table->unsignedBigInteger('member_id')->nullable();
            $table->unsignedBigInteger('uploaded_by');
            $table->unsignedBigInteger('meeting_id')->nullable();
            $table->unsignedBigInteger('loan_id')->nullable();
            $table->boolean('is_public')->default(false);
            $table->integer('download_count')->default(0);
            $table->timestamps();

            $table->foreign('member_id')->references('id')->on('members')->onDelete('set null');
            $table->foreign('uploaded_by')->references('id')->on('users');
            $table->foreign('meeting_id')->references('id')->on('meetings')->onDelete('set null');
            $table->foreign('loan_id')->references('id')->on('loans')->onDelete('set null');
            $table->index('document_type');
            $table->index('member_id');
            $table->index('is_public');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};