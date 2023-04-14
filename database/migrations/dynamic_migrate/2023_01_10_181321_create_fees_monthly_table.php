<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFeesMonthlyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fees_monthly', function (Blueprint $table) {
            $table->id();
            $table->integer('fees_type');
            $table->integer('student_id');
            $table->date('date');
            $table->string('month');
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
        Schema::dropIfExists('fees_monthly');
    }
}
