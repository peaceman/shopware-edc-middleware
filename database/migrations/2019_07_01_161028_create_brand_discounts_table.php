<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBrandDiscountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('brand_discounts', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('brand_id');
            $table->unsignedBigInteger('edc_feed_id');

            $table->unsignedInteger('value')->comment('discount percentage');

            $table->timestamp('current_until')->nullable();

            $table->foreign('brand_id', 'bd_b_fk')
                ->references('id')->on('brands')
                ->onUpdate('cascade')->onDelete('cascade');

            $table->foreign('edc_feed_id', 'bd_ef_fk')
                ->references('id')->on('edc_feeds')
                ->onUpdate('cascade')->onDelete('restrict');

            $table->index(['brand_id', 'current_until'], 'bd_bi_cu_idx');

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
        Schema::dropIfExists('brand_discounts');
    }
}
