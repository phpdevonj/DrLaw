<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDobLicenseExpirationSsnToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->date('date_of_birth')->nullable()->after('email');
            $table->string('license_number')->nullable()->after('date_of_birth');
            $table->date('license_expiration_date')->nullable()->after('license_number');
            $table->string('social_security_number')->nullable()->after('license_expiration_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'date_of_birth',
                'license_number',
                'license_expiration_date',
                'social_security_number',
            ]);
        });
    }
}
