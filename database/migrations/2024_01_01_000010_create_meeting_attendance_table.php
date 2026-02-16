<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meeting_attendance', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('meeting_id');
            $table->unsignedBigInteger('member_id');
            $table->enum('status', ['present', 'absent', 'excused'])->default('absent');
            $table->timestamp('check_in_time')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('meeting_id')->references('id')->on('meetings')->onDelete('cascade');
            $table->foreign('member_id')->references('id')->on('members')->onDelete('cascade');
            $table->unique(['meeting_id', 'member_id']);
            $table->index('meeting_id');
            $table->index('member_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meeting_attendance');
    }
};