<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEdcProductVariantDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('edc_product_variant_data', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('product_variant_id');
            $table->unsignedBigInteger('feed_part_stock_id')->nullable();
            $table->timestamp('current_until')->nullable();

            $table->foreign('product_variant_id', 'epvd_epv_fk')
                ->references('id')->on('edc_product_variants')
                ->onUpdate('cascade')->onDelete('cascade');

            $table->foreign('feed_part_stock_id', 'epvd_efps_fk')
                ->references('id')->on('edc_feed_part_stocks')
                ->onUpdate('cascade')->onDelete('restrict');

            $table->index(['product_variant_id', 'current_until'], 'epvd_pvi_cu_idx');

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
        Schema::dropIfExists('edc_product_variant_data');
    }
}
