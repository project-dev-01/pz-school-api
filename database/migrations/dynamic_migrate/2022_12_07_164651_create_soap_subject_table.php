<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSoapSubjectTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('soap_subject', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('header');
            $table->text('body');
            $table->integer('soap_type_id');
            $table->date('date');
            $table->integer('referred_by');
            $table->integer('student_id');
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
        Schema::dropIfExists('soap_subject');
    }
}
