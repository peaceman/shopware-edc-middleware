<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEdcProductVariantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('edc_product_variants', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('product_id');
            $table->string('edc_id');

            $table->foreign('product_id', 'epv_ep_fk')
                ->references('id')->on('edc_products')
                ->onUpdate('cascade')->onDelete('cascade');

            $table->unique('edc_id', 'epv_edc_id_uq');

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
        Schema::dropIfExists('edc_product_variants');
    }
}
