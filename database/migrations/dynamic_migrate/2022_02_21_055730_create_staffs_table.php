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
            $table->string('staff_id');
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('employment_status')->nullable();
            $table->string('short_name')->nullable();
            $table->string('department_id');
            $table->string('designation_id');
            $table->string('staff_qualification_id')->nullable(); 
            $table->string('stream_type_id')->nullable();
            $table->string('joining_date');
            $table->date('birthday');
            $table->string('gender');
            $table->string('religion');
            $table->string('race');
            $table->string('height')->nullable();
            $table->string('weight')->nullable();
            $table->string('allergy')->nullable();
            $table->string('blood_group')->nullable();
            $table->string('city');
            $table->string('state');
            $table->string('country');
            $table->string('post_code')->nullable();
            $table->text('present_address');
            $table->text('permanent_address');
            $table->string('mobile_no');
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
