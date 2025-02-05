<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePointsHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('points_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('ride_request_id')->nullable();
            $table->string('type')->nullable()->comment('credit,debit');
            $table->string('transaction_type')->nullable();
            $table->double('amount')->nullable()->default('0');
            $table->double('balance')->nullable()->default('0');
            $table->text('description')->nullable();
            $table->datetime('datetime')->nullable(); 
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('ride_request_id')->references('id')->on('ride_requests')->onDelete('cascade');
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
        Schema::dropIfExists('points_histories');
    }
}
