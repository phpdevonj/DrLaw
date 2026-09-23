<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Multi-Account Auth: Allow same email/contact_number/username across different user_type values.
     * Replaces global UNIQUE constraints with composite UNIQUE(column, user_type).
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $indexes = Schema::getIndexes('users');
            $indexNames = array_column($indexes, 'name');

            // Drop existing global unique indexes if they exist
            if (in_array('users_email_unique', $indexNames)) {
                $table->dropUnique('users_email_unique');
            }
            if (in_array('users_username_unique', $indexNames)) {
                $table->dropUnique('users_username_unique');
            }
            if (in_array('users_contact_number_unique', $indexNames)) {
                $table->dropUnique('users_contact_number_unique');
            }

            // Add composite unique indexes scoped by user_type
            if (!in_array('users_email_user_type_unique', $indexNames)) {
                $table->unique(['email', 'user_type'], 'users_email_user_type_unique');
            }
            if (!in_array('users_contact_number_user_type_unique', $indexNames)) {
                $table->unique(['contact_number', 'user_type'], 'users_contact_number_user_type_unique');
            }
            if (!in_array('users_username_user_type_unique', $indexNames)) {
                $table->unique(['username', 'user_type'], 'users_username_user_type_unique');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop composite unique indexes
            $table->dropUnique('users_email_user_type_unique');
            $table->dropUnique('users_contact_number_user_type_unique');
            $table->dropUnique('users_username_user_type_unique');

            // Restore original global unique indexes
            $table->unique('email', 'users_email_unique');
            $table->unique('username', 'users_username_unique');
        });
    }
};
