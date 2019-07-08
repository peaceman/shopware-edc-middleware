<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSwArticlesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sw_articles', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('edc_product_id');
            $table->string('sw_id');

            $table->foreign('edc_product_id', 'swa_ep_fk')
                ->references('id')->on('edc_products')
                ->onUpdate('cascade')->onDelete('restrict');

            $table->unique('sw_id', 'swa_sw_id_uq');

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
        Schema::dropIfExists('sw_articles');
    }
}
