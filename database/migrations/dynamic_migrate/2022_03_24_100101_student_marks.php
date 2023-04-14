<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class StudentMarks extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('student_marks', function (Blueprint $table) {
            $table->id();
            $table->integer('student_id');          
            $table->integer('class_id');
            $table->integer('section_id');
            $table->integer('subject_id');
            $table->integer('paper_id');
            $table->integer('grade_category');
            $table->integer('semester_id');
            $table->integer('session_id');
            $table->integer('exam_id');
            $table->string('score')->nullable();
            $table->string('pass_fail')->nullable();
            $table->string('status')->nullable();
            $table->string('grade');
            $table->string('ranking')->nullable();
            $table->text('memo')->nullable();
            $table->integer('academic_session_id')->default('0');
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
        //
    }
}
