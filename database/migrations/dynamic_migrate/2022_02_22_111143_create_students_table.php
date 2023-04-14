<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStudentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->integer('parent_id');
            $table->string('father_id')->nullable();
            $table->string('mother_id')->nullable();
            $table->string('guardian_id')->nullable();
            $table->integer('relation')->nullable();
            $table->string('year');
            $table->string('register_no');
            $table->string('roll_no');
            $table->date('admission_date');
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('gender')->nullable();
            $table->string('birthday')->nullable();
            $table->text('passport')->nullable();
            $table->text('nric')->nullable();
            $table->string('religion')->nullable();
            $table->string('race')->nullable();
            $table->string('blood_group')->nullable();
            $table->string('mother_tongue')->nullable();
            $table->text('current_address')->nullable();
            $table->text('permanent_address')->nullable();
            $table->string('country')->nullable();
            $table->string('state')->nullable();
            $table->string('city')->nullable();
            $table->string('post_code')->nullable();
            $table->text('mobile_no');
            $table->integer('category_id')->nullable();
            $table->string('email');
            $table->integer('route_id')->nullable();
            $table->integer('vehicle_id')->nullable();
            $table->integer('hostel_id')->nullable();
            $table->integer('room_id')->nullable();
            $table->text('previous_details')->nullable();
            $table->string('photo')->nullable();    
            $table->enum('status', ['0', '1']);
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
        Schema::dropIfExists('students');
    }
}
