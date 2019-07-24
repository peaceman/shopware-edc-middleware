<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEdcOrderExportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('edc_order_exports', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('order_id');
            $table->string('status');
            $table->text('sent');
            $table->json('received');
            $table->timestamps();

            $table->foreign('order_id', 'eoe_so')
                ->references('id')->on('sw_orders')
                ->onUpdate('cascade')->onDelete('cascade');

            $table->index('status', 'eoe_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('edc_order_exports');
    }
}
