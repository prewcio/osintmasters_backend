<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVotesTable extends Migration
{
    public function up()
    {
        Schema::create('votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('poll_id')->constrained()->onDelete('cascade');
            $table->string('choice');
            $table->timestamps();

            // Ensure a user can only vote once per poll
            $table->unique(['user_id', 'poll_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('votes');
    }
} 