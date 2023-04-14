<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShortTestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('short_tests', function (Blueprint $table) {
            $table->id();
            $table->integer('student_id');
            $table->date('date');
            $table->integer('class_id');
            $table->integer('section_id');
            $table->integer('subject_id');
            $table->integer('semester_id');
            $table->integer('session_id');
            $table->integer('academic_session_id')->default('0');
            $table->text('test_name');
            $table->text('test_marks');
            $table->text('grade_status');
            $table->text('remarks')->nullable();
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
        Schema::dropIfExists('short_tests');
    }
}
