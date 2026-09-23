<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPaymentIntentColumnsToRideRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ride_requests', function (Blueprint $table) {
            $table->string('held_payment_intent_id')->nullable();
            $table->double('held_payment_amount')->nullable(); 
            $table->string('captured_payment_intent_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ride_requests', function (Blueprint $table) {
            $table->dropColumn([
                'held_payment_intent_id',
                'held_payment_amount',
                'captured_payment_intent_id',
            ]);
        });
    }
}
