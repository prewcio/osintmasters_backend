<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePollQuestionsTable extends Migration
{
    public function up()
    {
        Schema::create('poll_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('poll_id')->constrained()->onDelete('cascade');
            $table->string('question');
            $table->enum('type', ['single', 'multiple', 'text', 'scale']);
            $table->json('options')->nullable(); // For single/multiple choice questions
            $table->json('scale_config')->nullable(); // For scale questions (min, max, step)
            $table->integer('question_order')->default(0);
            $table->boolean('required')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('poll_questions');
    }
} 