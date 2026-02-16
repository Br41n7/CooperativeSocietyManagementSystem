<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('votes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('meeting_id');
            $table->string('question', 500);
            $table->enum('vote_type', ['yes_no', 'multiple_choice', 'open'])->default('yes_no');
            $table->json('options')->nullable();
            $table->timestamp('start_time')->nullable();
            $table->timestamp('end_time')->nullable();
            $table->enum('status', ['active', 'closed'])->default('active');
            $table->integer('total_votes')->default(0);
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('meeting_id')->references('id')->on('meetings')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users');
            $table->index('meeting_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('votes');
    }
};