<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTimetableBulkTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('timetable_bulk', function (Blueprint $table) {
            $table->id();
            $table->string('class_id');
            $table->string('break');
            $table->string('break_type')->nullable();
            $table->string('teacher_id')->nullable();
            $table->integer('semester_id')->nullable();
            $table->integer('session_id')->nullable();
            $table->string('class_room')->nullable();
            $table->time('time_start');
            $table->time('time_end');
            $table->string('day');
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
        Schema::dropIfExists('timetable_bulk');
    }
}
