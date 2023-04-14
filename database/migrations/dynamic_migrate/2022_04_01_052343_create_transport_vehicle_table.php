<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransportVehicleTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transport_vehicle', function (Blueprint $table) {
            $table->id();
            $table->string('vehicle_no');
            $table->string('capacity');
            $table->string('insurance_renewal');
            $table->string('driver_name');
            $table->string('driver_phone');
            $table->string('driver_license');
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
        Schema::dropIfExists('transport_vehicle');
    }
}
