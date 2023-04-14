<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHomeworkEvaluationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('homework_evaluation', function (Blueprint $table) {
            $table->id();
            $table->integer('homework_id');
            $table->integer('student_id');
            $table->text('remarks')->nullable();
            $table->text('teacher_remarks')->nullable();
            $table->string('rank')->nullable();
            $table->string('score_name')->nullable();
            $table->string('score_value')->nullable();
            $table->date('date')->nullable();
            $table->string('file');
            $table->string('status');
            $table->string('correction')->default('0');
            $table->date('evaluation_date')->nullable();
            $table->string('evaluated_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('homework_evaluation');
    }
}
