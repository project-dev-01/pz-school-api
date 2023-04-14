<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class StudentLeaves extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('student_leaves', function (Blueprint $table) {
            $table->id();
            $table->integer('student_id');
            $table->integer('parent_id');
            $table->integer('class_id');
            $table->integer('section_id');
            $table->date('from_leave');
            $table->date('to_leave');
            $table->string('reasonId')->nullable();   
            $table->string('reason')->nullable();  
            $table->string('remarks')->nullable();            
            $table->string('document')->nullable();
            $table->string('teacher_remarks')->nullable(); 
            $table->string('status')->nullable();
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
