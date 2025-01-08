<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSurgePricesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('surge_prices', function (Blueprint $table) {
            $table->id();
            $table->string('day');
            $table->string('type')->comment('fixed, multiply');
            $table->double('value')->nullable()->default('0');
            $table->text('from_time')->nullable();
            $table->text('to_time')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('surge_prices');
    }
}
