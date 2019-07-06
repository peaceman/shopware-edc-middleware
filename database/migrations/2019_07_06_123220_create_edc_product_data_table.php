<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEdcProductDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('edc_product_data', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('feed_part_product_id');
            $table->timestamp('current_until')->nullable();

            $table->foreign('product_id', 'epd_ep_fk')
                ->references('id')->on('edc_products')
                ->onUpdate('cascade')->onDelete('cascade');

            $table->foreign('feed_part_product_id', 'epd_efpp_fk')
                ->references('id')->on('edc_feed_part_products')
                ->onUpdate('cascade')->onDelete('restrict');

            $table->index(['product_id', 'current_until'], 'epd_pi_cu_idx');

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
        Schema::dropIfExists('edc_product_data');
    }
}
