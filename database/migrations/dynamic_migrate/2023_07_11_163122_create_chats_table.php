<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChatsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('chats', function (Blueprint $table) {
            $table->id();
            $table->string('chat_fromid');
            $table->string('chat_fromname');
            $table->string('chat_fromuser');
            $table->string('chat_toid');
            $table->string('chat_toname');
            $table->string('chat_touser');
            $table->binary('chat_content');
            $table->string('chat_status');
            $table->string('chat_document')->nullable();
            $table->string('chat_file_extension')->nullable();
            $table->string('msg_delivered_time')->nullable();
            $table->string('msg_view_time')->nullable();
            $table->integer('flag')->default('1');
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
        Schema::dropIfExists('chats');
    }
}
