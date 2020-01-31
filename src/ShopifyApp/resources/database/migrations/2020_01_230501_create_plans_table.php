<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePlansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->increments('id');

            // The type of plan, either Plan::CHARGE_RECURRING (1) or Plan::CHARGE_ONETIME (2)
            $table->integer('type');

            // Name of the plan
            $table->string('name');

            // Price of the plan
            $table->decimal('price', 8, 2);

            // Store the amount of the charge, this helps if you are experimenting with pricing
            $table->decimal('capped_amount', 8, 2)->nullable();

            // Terms for the usage charges
            $table->string('terms')->nullable();

            // Nullable in case of 0 trial days
            $table->integer('trial_days')->nullable();

            // Is a test plan or not
            $table->boolean('test')->default(false);

            // On-install
            $table->boolean('on_install')->default(false);

            // Provides created_at && updated_at columns
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('plans');
    }
}
