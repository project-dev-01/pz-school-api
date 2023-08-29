<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('user_id')->nullable();
            $table->integer('branch_id')->nullable();
            $table->string('role_id');
            $table->string('email')->unique();
            $table->string('picture')->nullable();
            $table->enum('status', ['0', '1']);
            $table->timestamp('email_verified_at')->nullable();
            $table->integer('login_attempt')->default('0');
            $table->string('password');
            $table->text('remember_token')->nullable();
            $table->timestamp('password_changed_at')->nullable();
            $table->text('session_id')->nullable();
            $table->text('google2fa_secret')->nullable();
            $table->enum('google2fa_secret_enable', ['0', '1'])->comment('0 for disable,1 for enable')->default('0');
            $table->enum('is_active', ['0', '1'])->comment('0 => Active, 1 => Not active')->default('0');
            $table->timestamp('last_seen')->nullable();
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
        Schema::dropIfExists('users');
    }
}
