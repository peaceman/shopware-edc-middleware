<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEdcOrderUpdatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('edc_order_updates', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('order_id');
            $table->string('status')->comment('shipped or backorder');
            $table->text('received');
            $table->timestamps();

            $table->foreign('order_id')
                ->references('id')->on('sw_orders')
                ->onUpdate('cascade')->onDelete('cascade');

            $table->index('status', 'eou_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('edc_order_updates');
    }
}
