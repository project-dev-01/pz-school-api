<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateForumPostsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('forum_posts', function (Blueprint $table) {
            $table->id();
            $table->integer('branch_id');
            $table->integer('user_id')->nullable();
            $table->string('user_name')->nullable();
            $table->string('topic_title');
            $table->string('topic_header');
            $table->string('types')->nullable();
            $table->text('body_content');
            $table->string('category');
            $table->string('tags')->nullable();
            $table->string('imagesorvideos')->nullable();
            $table->integer('threads_status');
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
        Schema::dropIfExists('forum_posts');
    }
}
