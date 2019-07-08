<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEdcProductImagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('edc_product_images', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('product_id');
            $table->string('identifier', 8);
            $table->string('filename');
            $table->string('etag');
            $table->unsignedBigInteger('file_id');

            $table->timestamps();

            $table->foreign('product_id', 'epi_ep_fk')
                ->references('id')->on('edc_products')
                ->onUpdate('cascade')->onDelete('restrict');

            $table->index('identifier', 'epi_identifier_idx');

            $table->foreign('file_id', 'epi_rf_fk')
                ->references('id')->on('resource_files')
                ->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('edc_product_images');
    }
}
