<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNotificationTimestampsAndScheduledAtToRideRequests extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ride_requests', function (Blueprint $table) {
            $table->timestamp('last_24hr_notification_sent_at')->nullable()->after('status');
            $table->timestamp('last_1hr_notification_sent_at')->nullable()->after('last_24hr_notification_sent_at');
            $table->timestamp('scheduled_at')->nullable()->after('is_schedule');
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
            $table->dropColumn(['last_24hr_notification_sent_at', 'last_1hr_notification_sent_at', 'scheduled_at']);
        });
    }
}
