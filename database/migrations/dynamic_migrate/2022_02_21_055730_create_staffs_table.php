<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStaffsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('staffs', function (Blueprint $table) {
            $table->id();
            $table->string('staff_id')->nullable();
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('employment_status')->nullable();
            $table->string('short_name')->nullable();
            $table->string('department_id')->nullable();
            $table->string('designation_id')->nullable();
            $table->string('staff_qualification_id')->nullable(); 
            $table->string('stream_type_id')->nullable();
            $table->string('joining_date')->nullable();
            $table->date('birthday')->nullable();
            $table->string('gender')->nullable();
            $table->string('religion')->nullable();
            $table->string('race')->nullable();
            $table->string('height')->nullable();
            $table->string('weight')->nullable();
            $table->string('allergy')->nullable();
            $table->string('blood_group')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->string('post_code')->nullable();
            $table->text('present_address')->nullable();
            $table->text('permanent_address')->nullable();
            $table->string('mobile_no')->nullable();
            $table->string('email')->unique();
            $table->string('photo')->nullable();
            $table->string('facebook_url')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->string('twitter_url')->nullable();
            $table->string('salary_grade')->nullable();
            $table->string('staff_category')->nullable();
            $table->string('staff_position')->nullable();
            $table->string('nric_number')->nullable();
            $table->string('passport')->nullable();
            $table->enum('status', ['0', '1']);
            $table->enum('is_active', ['0', '1'])->comment('0 => Active, 1 => Not active');
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
        Schema::dropIfExists('staffs');
    }
}
