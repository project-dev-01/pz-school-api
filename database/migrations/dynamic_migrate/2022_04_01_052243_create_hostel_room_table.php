<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHostelRoomTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hostel_room', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('hostel_id');
            $table->string('block');
            $table->string('floor');
            $table->integer('no_of_beds');
            $table->string('bed_fee');
            $table->text('remarks')->nullable();
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
        Schema::dropIfExists('hostel_room');
    }
}
