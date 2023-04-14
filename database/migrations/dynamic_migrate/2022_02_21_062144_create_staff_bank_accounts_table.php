<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStaffBankAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('staff_bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->integer('staff_id');
            $table->string('bank_name');
            $table->string('holder_name');
            $table->string('bank_branch');
            $table->string('bank_address');
            $table->string('ifsc_code');
            $table->string('account_no');
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
        Schema::dropIfExists('staff_bank_accounts');
    }
}
