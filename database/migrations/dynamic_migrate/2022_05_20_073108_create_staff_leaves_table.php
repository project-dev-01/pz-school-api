<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStaffLeavesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('staff_leaves', function (Blueprint $table) {
            $table->id();
            $table->integer('staff_id');
            $table->date('from_leave');
            $table->date('to_leave');
            $table->integer('leave_type');
            $table->integer('total_leave');
            $table->string('reason_id')->nullable();
            $table->text('assiner_remarks')->nullable();
            $table->integer('assiner_id')->nullable();
            $table->text('remarks')->nullable();
            $table->string('document')->nullable();
            $table->string('status')->nullable();
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
        Schema::dropIfExists('staff_leaves');
    }
}
