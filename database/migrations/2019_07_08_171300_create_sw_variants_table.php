<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSwVariantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sw_variants', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('article_id');
            $table->unsignedBigInteger('edc_product_variant_id');
            $table->string('sw_id');

            $table->foreign('article_id', 'swv_swa_fk')
                ->references('id')->on('sw_articles')
                ->onUpdate('cascade')->onDelete('cascade');

            $table->foreign('edc_product_variant_id', 'swv_epv_fk')
                ->references('id')->on('edc_product_variants')
                ->onUpdate('cascade')->onDelete('restrict');

            $table->unique('sw_id', 'swv_sw_id_uq');

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
        Schema::dropIfExists('sw_varians');
    }
}
