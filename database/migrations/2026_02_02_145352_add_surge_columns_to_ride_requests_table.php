<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSurgeColumnsToRideRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ride_requests', function (Blueprint $table) {
            $table->string('surge_type', 50)->nullable()->after('expenses_charge');
            $table->decimal('surge_value', 10, 2)->nullable()->after('surge_type');
            $table->decimal('surge_amount', 10, 2)->default(0)->after('surge_value');
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
                'surge_type',
                'surge_value',
                'surge_amount',
            ]);
        });
    }
}
