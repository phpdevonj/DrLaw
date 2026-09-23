<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTimeFareCompanyFeeAndExpensesChargesColumnsToRideRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ride_requests', function (Blueprint $table) {
            // Time fare tier rates per minute
            $table->double('time_fare_short_ride')->nullable()->after('per_minute_waiting_charge');
            $table->double('time_fare_moderate_ride')->nullable()->after('time_fare_short_ride');
            $table->double('time_fare_long_ride')->nullable()->after('time_fare_moderate_ride');
            $table->double('per_minute_time_fare_charge')->nullable()->after('time_fare_long_ride');

            // Company fee logic
            $table->double('company_fee_threshold')->nullable()->after('per_minute_time_fare_charge');
            $table->double('company_fee_below_threshold')->nullable()->after('company_fee_threshold');
            $table->double('company_fee_above_threshold')->nullable()->after('company_fee_below_threshold');
            $table->double('company_fee_charge')->nullable()->after('company_fee_above_threshold');

            // Expenses
            $table->double('expenses')->nullable()->after('company_fee_charge');
            $table->double('expenses_charge')->nullable()->after('expenses');
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
                'time_fare_short_ride',
                'time_fare_moderate_ride',
                'time_fare_long_ride',
                'per_minute_time_fare_charge',
                'company_fee_threshold',
                'company_fee_below_threshold',
                'company_fee_above_threshold',
                'company_fee_charge',
                'expenses',
                'expenses_charge'
            ]);
        });
    }
}
