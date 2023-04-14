<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTimetableClassTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('timetable_class', function (Blueprint $table) {
            $table->id();
            $table->integer('class_id');
            $table->integer('section_id');
            $table->string('break')->nullable();
            $table->string('break_type')->nullable();
            $table->integer('subject_id')->nullable();
            $table->string('teacher_id')->nullable();
            $table->integer('semester_id')->nullable();
            $table->integer('session_id')->nullable();
            $table->string('class_room')->nullable();
            $table->time('time_start')->nullable();
            $table->time('time_end')->nullable();
            $table->string('day')->nullable();
            $table->integer('bulk_id')->nullable();
            $table->string('type')->nullable();
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
        Schema::dropIfExists('timetable_class');
    }
}
