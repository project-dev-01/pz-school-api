<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFeesSemesterTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fees_semester', function (Blueprint $table) {
            $table->id();
            $table->integer('fees_type');
            $table->integer('student_id');
            $table->date('date');
            $table->string('semester_id');
            $table->string('payment_status')->nullable();
            $table->string('collect_by');
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
        Schema::dropIfExists('fees_semester');
    }
}
