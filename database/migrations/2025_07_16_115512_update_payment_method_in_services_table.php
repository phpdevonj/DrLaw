<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class UpdatePaymentMethodInServicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('services', function (Blueprint $table) {
            DB::statement("ALTER TABLE services MODIFY payment_method ENUM('cash_wallet', 'cash', 'wallet', 'card','card_wallet') DEFAULT 'wallet'");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('services', function (Blueprint $table) {
            DB::statement("ALTER TABLE services MODIFY payment_method ENUM('cash_wallet', 'cash', 'wallet') DEFAULT 'cash'");
        });
    }
}
