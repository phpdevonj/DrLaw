<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEarlyCompletionColumnsToRideRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ride_requests', function (Blueprint $table) {
            $table->string('destination_latitude')->nullable()->after('end_address');
            $table->string('destination_longitude')->nullable()->after('destination_latitude');
            $table->text('destination_address')->nullable()->after('destination_longitude');
            $table->boolean('completed_early')->default(0)->nullable()->after('reason');
            $table->text('early_completion_reason')->nullable()->after('completed_early');
            $table->double('remaining_distance')->nullable()->after('distance');
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
                'destination_latitude',
                'destination_longitude',
                'destination_address',
                'completed_early',
                'early_completion_reason',
                'remaining_distance'
            ]);
        });
    }
}
