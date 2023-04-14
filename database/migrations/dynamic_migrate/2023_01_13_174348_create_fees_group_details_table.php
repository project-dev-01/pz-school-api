<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFeesGroupDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fees_group_details', function (Blueprint $table) {
            $table->id();
            $table->integer('fees_group_id');
            $table->integer('fees_type_id');
            $table->integer('payment_mode_id');
            $table->decimal('amount', $precision = 10, $scale = 0);
            $table->string('monthly')->nullable();
            $table->string('semester')->nullable();
            $table->string('yearly')->nullable();
            $table->date('due_date');
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
        Schema::dropIfExists('fees_group_details');
    }
}
