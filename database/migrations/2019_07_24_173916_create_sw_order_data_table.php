<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSwOrderDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sw_order_details', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('order_id');
            $table->json('data');
            $table->timestamps();

            $table->foreign('order_id', 'sode_so_fk')
                ->references('id')->on('sw_orders')
                ->onUpdate('cascade')->onDelete('cascade');
        });

        Schema::create('sw_order_data', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('order_detail_id');
            $table->string('sw_transfer_status');
            $table->string('edc_transfer_status');
            $table->string('edc_order_number')->nullable();
            $table->string('tracking_number')->nullable();
            $table->timestamp('current_until')->nullable();
            $table->timestamps();

            $table->foreign('order_id', 'soda_so_fk')
                ->references('id')->on('sw_orders')
                ->onUpdate('cascade')->onDelete('cascade');

            $table->foreign('order_detail_id', 'soda_sode_fk')
                ->references('id')->on('sw_order_details')
                ->onUpdate('cascade')->onDelete('restrict');

            $table->index('sw_transfer_status', 'soda_sts_idx');
            $table->index('edc_transfer_status', 'soda_ets_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sw_order_details');
        Schema::dropIfExists('sw_order_data');
    }
}
