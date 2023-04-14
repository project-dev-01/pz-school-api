<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class StudentSubjectdivisionInst extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('student_subjectdivision_inst', function (Blueprint $table) {
            $table->id();
            $table->integer('class_id');
            $table->integer('student_id');
            $table->integer('section_id');
            $table->integer('subject_id');       
            $table->integer('exam_id');  
            $table->text('subject_division')->nullable();
            $table->text('subjectdivision_scores')->nullable();
            $table->integer('total_score');     
            $table->string('pass_fail')->nullable();
            $table->string('status')->nullable();
            $table->string('grade');
            $table->integer('ranking');      
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
