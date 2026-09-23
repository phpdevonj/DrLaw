<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTimeFareAndCompanyFeeColumnsToServicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('services', function (Blueprint $table) {
            // Time fare tier rates per minute
            $table->double('time_fare_short_ride')->nullable()->after('cancellation_fee');
            $table->double('time_fare_moderate_ride')->nullable()->after('time_fare_short_ride');
            $table->double('time_fare_long_ride')->nullable()->after('time_fare_moderate_ride');

            // Company fee logic
            $table->double('company_fee_threshold')->nullable()->after('time_fare_long_ride');
            $table->double('company_fee_below_threshold')->nullable()->after('company_fee_threshold');
            $table->double('company_fee_above_threshold')->nullable()->after('company_fee_below_threshold');

            // Expenses
            $table->double('expenses')->nullable()->after('company_fee_above_threshold');
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
            $table->dropColumn([
                'time_fare_short_ride',
                'time_fare_moderate_ride',
                'time_fare_long_ride',
                'company_fee_threshold',
                'company_fee_below_threshold',
                'company_fee_above_threshold',
                'expenses'
            ]);
        });
    }
}
