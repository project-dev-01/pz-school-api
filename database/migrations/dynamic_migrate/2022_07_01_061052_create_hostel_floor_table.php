<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHostelFloorTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hostel_floor', function (Blueprint $table) {
            $table->id();
            $table->string('floor_name');
            $table->integer('block_id');
            $table->string('floor_warden');
            $table->string('floor_leader')->nullable();
            $table->string('total_room');
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
        Schema::dropIfExists('hostel_floor');
    }
}
