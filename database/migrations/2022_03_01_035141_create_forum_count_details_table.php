<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateForumCountDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('forum_count_details', function (Blueprint $table) {
            $table->id();
            $table->integer('created_post_id');            
            $table->integer('branch_id');
            $table->integer('user_id');
            $table->string('user_name');
            $table->integer('likes');
            $table->integer('dislikes');
            $table->integer('favorite');
            $table->integer('replies');
            $table->integer('views');
            $table->string('activity');
            $table->string('flag');
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
        Schema::dropIfExists('forum_count_details');
    }
}
