<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateForumPostReplieCountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('forum_post_replie_counts', function (Blueprint $table) {
            $table->id();
            $table->integer('created_post_id');      
            $table->integer('created_post_replies_id');       
            $table->integer('branch_id');
            $table->integer('user_id');
            $table->string('user_name');
            $table->string('likes');
            $table->string('dislikes');
            $table->string('favorits');
            $table->integer('flag');
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
        Schema::dropIfExists('forum_post_replie_counts');
    }
}
