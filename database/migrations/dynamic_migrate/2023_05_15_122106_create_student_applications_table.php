<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStudentApplicationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('student_applications', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('gender')->nullable();
            $table->string('date_of_birth')->nullable();
            $table->integer('mobile_no');
            $table->string('email');
            $table->text('address_1');
            $table->text('address_2')->nullable();
            $table->string('country');
            $table->string('state');
            $table->string('city');
            $table->string('postal_code');
            $table->string('grade');
            $table->string('school_year');
            $table->string('school_last_attended');
            $table->text('school_address_1');
            $table->text('school_address_2')->nullable();
            $table->string('school_country');
            $table->string('school_city');
            $table->string('school_state');
            $table->string('school_postal_code');
            $table->string('parent_type');
            $table->string('parent_relation');
            $table->string('parent_first_name');
            $table->string('parent_last_name')->nullable();
            $table->string('parent_phone_number');
            $table->string('parent_occupation');
            $table->string('parent_email');
            $table->string('secondary_type');
            $table->string('secondary_relation');
            $table->string('secondary_first_name');
            $table->string('secondary_last_name')->nullable();
            $table->string('secondary_phone_number');
            $table->string('secondary_occupation');
            $table->string('secondary_email');
            $table->string('emergency_contact_person');
            $table->string('emergency_contact_first_name');
            $table->string('emergency_contact_last_name')->nullable();
            $table->string('emergency_contact_phone_number');
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
        Schema::dropIfExists('student_applications');
    }
}
