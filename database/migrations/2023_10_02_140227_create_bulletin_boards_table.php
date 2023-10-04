<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bulletin_boards', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->longText('discription')->nullable();
            $table->string('file');
            $table->string('target_user');
            $table->integer('class_id')->nullable();
            $table->integer('section_id')->nullable();
            $table->integer('student_id')->nullable();
            $table->integer('parent_id')->nullable();
            $table->integer('department_id')->nullable();
            $table->integer('publish')->nullable();
            $table->date('publish_date')->nullable();
            $table->time('publish_time')->nullable();
            $table->tinyInteger('status');
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->string('deleted_by')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bulletin_boards');
    }
};
