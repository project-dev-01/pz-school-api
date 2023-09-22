<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAssignLeaveApprovalTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('assign_leave_approval', function (Blueprint $table) {
            $table->id();
            $table->integer('staff_id');
            $table->integer('level_one_staff_id')->nullable();
            $table->integer('level_two_staff_id')->nullable();
            $table->integer('level_three_staff_id')->nullable();
            // $table->integer('assigner_staff_id')->nullable();
            $table->string('created_by')->nullable();
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
        Schema::dropIfExists('assign_leave_approval');
    }
}
