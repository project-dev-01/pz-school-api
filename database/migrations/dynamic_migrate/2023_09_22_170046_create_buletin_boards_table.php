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
        Schema::create('buletin_boards', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->longText('discription')->nullable();
            $table->string('file');
            $table->string('target_user');
            $table->date('publish_date');
            $table->time('publish_time')->nullable();
            $table->tinyInteger('status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('buletin_boards');
    }
};
