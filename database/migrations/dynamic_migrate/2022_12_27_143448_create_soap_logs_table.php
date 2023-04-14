<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSoapLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('soap_logs', function (Blueprint $table) {
            $table->id();
            $table->integer('student_id');
            $table->integer('staff_id');
            $table->integer('soap_id');
            $table->integer('soap_type');
            $table->string('soap_text');
            $table->string('type');
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
        Schema::dropIfExists('soap_logs');
    }
}
