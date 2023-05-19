<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndexOnSubjectAssignsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('subject_assigns', function (Blueprint $table) {
            //
            $table->index(['class_id', 'section_id']);
            $table->index(['subject_id', 'teacher_id']);
            $table->index(['type', 'academic_session_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('subject_assigns', function (Blueprint $table) {
            //
            $table->dropIndex(['class_id', 'section_id']);
            $table->dropIndex(['subject_id', 'teacher_id']);
            $table->dropIndex(['type', 'academic_session_id']);
        });
    }
}
