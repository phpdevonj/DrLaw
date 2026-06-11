<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRideTipsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ride_tips', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ride_request_id');
            $table->double('tip_amount')->nullable()->default('0');
            $table->string('payment_type')->nullable(); // e.g., card, wallet, cash
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');
            $table->enum('received_by', ['driver', 'admin'])->default('driver');
            $table->string('payment_intent_id')->nullable();
            $table->timestamps();

            $table->foreign('ride_request_id')
                  ->references('id')
                  ->on('ride_requests')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ride_tips');
    }
}
