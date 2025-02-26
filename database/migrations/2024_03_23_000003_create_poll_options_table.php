<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePollOptionsTable extends Migration
{
    public function up()
    {
        Schema::create('poll_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('poll_question_id')->constrained()->onDelete('cascade');
            $table->string('option_text');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('poll_options');
    }
} 