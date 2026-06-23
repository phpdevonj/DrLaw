<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMobileMoneyRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mobile_money_requests', function (Blueprint $user) {
            $user->id();
            $user->foreignId('user_id')->constrained()->onDelete('cascade');
            $user->string('reference_id')->unique();
            $user->string('provider_transaction_id')->nullable();
            $user->decimal('amount', 16, 2);
            $user->string('currency', 10)->default('XAF');
            $user->string('phone_number');
            $user->string('status')->default('pending');
            $user->json('response_payload')->nullable();
            $user->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('mobile_money_requests');
    }
}
