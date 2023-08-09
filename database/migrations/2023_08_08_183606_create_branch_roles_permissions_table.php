<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBranchRolesPermissionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('branch_roles_permissions', function (Blueprint $table) {
            $table->id();
            $table->integer('branch_id');
            $table->string('role_id');
            $table->enum('permission_status', ['0', '1'])->default('0')->comment('0 => access, 1 => denied');
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->string('deleted_by')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('branch_roles_permissions');
    }
}
