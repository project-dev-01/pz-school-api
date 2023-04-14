<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFeesPaymentHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fees_payment_history', function (Blueprint $table) {
            $table->id();
            $table->integer('student_id');
            $table->integer('allocation_id');
            $table->integer('fees_type_id');
            $table->integer('fees_group_id');
            $table->integer('fees_group_details_id');
            $table->string('monthly')->nullable();
            $table->string('semester')->nullable();
            $table->string('yearly')->nullable();
            $table->integer('payment_mode_id');
            $table->integer('payment_status_id');
            $table->string('collect_by');
            $table->decimal('amount', $precision = 18, $scale = 2);
            $table->decimal('discount', $precision = 18, $scale = 2);
            $table->decimal('fine', $precision = 18, $scale = 2);
            $table->string('pay_via')->nullable();
            $table->text('remarks')->nullable();
            $table->date('date')->nullable();
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
        Schema::dropIfExists('fees_payment_history');
    }
}
