<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTimetableExamTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('timetable_exam', function (Blueprint $table) {
            $table->id();
            $table->integer('exam_id');
            $table->integer('class_id');
            $table->integer('section_id');
            $table->integer('semester_id');
            $table->integer('session_id');
            $table->integer('subject_id');
            $table->string('paper_id')->nullable();
            $table->time('time_start');
            $table->time('time_end');
            $table->integer('hall_id')->nullable();
            $table->string('distributor_type')->nullable();
            $table->string('distributor')->nullable();
            $table->string('distributor_id')->nullable();
            $table->date('exam_date');
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
        Schema::dropIfExists('timetable_exam');
    }
}
