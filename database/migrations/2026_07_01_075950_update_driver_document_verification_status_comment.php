<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("
            ALTER TABLE driver_documents
            MODIFY COLUMN is_verified TINYINT NULL DEFAULT 0
            COMMENT '0-pending,1-approved,2-rejected,3-expired'
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("
            ALTER TABLE driver_documents
            MODIFY COLUMN is_verified TINYINT NULL DEFAULT 0
            COMMENT '0-pending,1-approved,2-rejected'
        ");
    }
};
