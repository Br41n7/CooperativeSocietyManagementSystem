<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vote_responses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vote_id');
            $table->unsignedBigInteger('member_id');
            $table->string('response');
            $table->timestamp('voted_at');

            $table->foreign('vote_id')->references('id')->on('votes')->onDelete('cascade');
            $table->foreign('member_id')->references('id')->on('members')->onDelete('cascade');
            $table->unique(['vote_id', 'member_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vote_responses');
    }
};