<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToUserBankAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_bank_accounts', function (Blueprint $table) {
            $table->text('bank_address')->nullable()->after('account_number');
            $table->string('routing_number')->nullable()->after('bank_address');
            $table->string('bank_iban')->nullable()->after('routing_number');
            $table->string('bank_swift')->nullable()->after('bank_iban');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_bank_accounts', function (Blueprint $table) {
            //
        });
    }
}
