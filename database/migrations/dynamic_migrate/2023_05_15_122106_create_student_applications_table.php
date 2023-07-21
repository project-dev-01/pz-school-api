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
            $table->string('academic_grade');
            $table->string('academic_year');
            $table->string('grade');
            $table->string('school_year');
            $table->string('school_last_attended');
            $table->text('school_address_1');
            $table->text('school_address_2')->nullable();
            $table->string('school_country');
            $table->string('school_city');
            $table->string('school_state');
            $table->string('school_postal_code');
            $table->string('father_first_name');
            $table->string('father_last_name')->nullable();
            $table->string('father_phone_number');
            $table->string('father_occupation');
            $table->string('father_email');
            $table->string('mother_first_name');
            $table->string('mother_last_name')->nullable();
            $table->string('mother_phone_number');
            $table->string('mother_occupation');
            $table->string('mother_email');
            $table->string('guardian_first_name');
            $table->string('guardian_last_name')->nullable();
            $table->string('guardian_relation');
            $table->string('guardian_phone_number');
            $table->string('guardian_occupation');
            $table->string('guardian_email');
            $table->integer('staus');
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
