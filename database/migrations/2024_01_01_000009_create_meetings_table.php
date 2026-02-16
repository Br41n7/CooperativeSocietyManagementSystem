<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meetings', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('meeting_type', ['agm', 'executive', 'committee', 'emergency', 'regular']);
            $table->dateTime('meeting_date');
            $table->string('venue');
            $table->text('agenda');
            $table->text('minutes')->nullable();
            $table->enum('status', ['scheduled', 'ongoing', 'completed', 'cancelled'])->default('scheduled');
            $table->boolean('notify_members')->default(true);
            $table->integer('total_attendees')->default(0);
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users');
            $table->index('meeting_date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meetings');
    }
};