<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Onepage\Approval\Enums\ApprovalStatus;

class CreateApprovalFlowsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
       Schema::create('approval_flows', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('model_type')->nullable();
			$table->unsignedBigInteger('model_id')->unsigned()->nullable();;
            $table->json('values')->nullable();
            $table->unsignedBigInteger('parent_id')->unsigned()->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->unsignedInteger('version_status')->default(30);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('submitted_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->unsignedBigInteger('published_by')->nullable();
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
        Schema::drop('approval_flows');
    }
}
