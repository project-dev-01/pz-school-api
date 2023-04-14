<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateParentTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('parent', function (Blueprint $table) {
            $table->id();
            $table->string('ref_father_id')->nullable();
            $table->string('ref_mother_id')->nullable();
            $table->string('ref_guardian_id')->nullable();
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('gender')->nullable();
            $table->string('date_of_birth')->nullable();
            $table->string('passport')->nullable();
            $table->string('nric')->nullable();
            $table->string('blood_group')->nullable();
            $table->string('occupation');
            $table->string('income')->nullable();
            $table->string('education')->nullable();
            $table->string('email');
            $table->text('mobile_no');
            $table->integer('race')->nullable();
            $table->integer('religion')->nullable();
            $table->text('address')->nullable();
            $table->text('address_2')->nullable();
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('post_code')->nullable();
            $table->string('photo')->nullable();
            $table->string('facebook_url')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->string('twitter_url')->nullable();    
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
        Schema::dropIfExists('parent');
    }
}
