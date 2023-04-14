<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->integer('from_id')->nullable();
            $table->string('to_id')->nullable();
            $table->string('to_type')->comment('1 => Message, 2 => Group Message');
            $table->text('message');
            $table->tinyInteger('status')->comment('0 for unread,1 for seen');
            $table->tinyInteger('message_type')->comment('0- text message, 1- image, 2- pdf, 3- doc, 4- voice');
            $table->text('file_name')->nullable();
            $table->integer('reply_to')->nullable();
            $table->longText('url_details')->nullable();
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
        Schema::dropIfExists('messages');
    }
}
