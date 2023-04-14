<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCalendorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('calendors', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->integer('class_id')->nullable();
            $table->integer('section_id')->nullable();
            $table->integer('subject_id')->nullable();
            $table->string('timing')->nullable();
            $table->string('teacher_id')->nullable();
            $table->string('sem_id')->nullable();
            $table->integer('session_id');
            $table->integer('time_table_id')->nullable();
            $table->integer('bulk_id')->nullable();
            $table->integer('event_id')->nullable();
            $table->integer('group_id')->nullable();
            $table->dateTime('start')->nullable();
            $table->dateTime('end')->nullable();
            $table->text('description')->nullable();
            $table->string('task_color')->nullable();
            $table->integer('login_id')->nullable();
            $table->integer('relief_assignment_id')->nullable();
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
        Schema::dropIfExists('calendors');
    }
}
