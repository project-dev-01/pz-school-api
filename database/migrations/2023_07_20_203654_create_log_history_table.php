<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLogHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('log_history', function (Blueprint $table) {
            $table->id();
            $table->integer('login_id');
            $table->integer('user_id');
            $table->integer('role_id');
            $table->integer('branch_id');
            $table->string('ip_address');
            $table->string('device');
            $table->string('browser');
            $table->string('os');
            $table->timestamp('login_time');
            $table->timestamp('logout_time');
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
        Schema::dropIfExists('log_history');
    }
}
