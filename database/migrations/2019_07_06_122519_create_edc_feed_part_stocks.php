<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEdcFeedPartStocks extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('edc_feed_part_stocks', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('file_id');
            $table->unsignedBigInteger('full_feed_id');

            $table->foreign('file_id', 'efps_rf_fk')
                ->references('id')->on('resource_files')
                ->onUpdate('cascade')->onDelete('restrict');

            $table->foreign('full_feed_id', 'efps_ef_fk')
                ->references('id')->on('edc_feeds')
                ->onUpdate('cascade')->onDelete('restrict');

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
        Schema::dropIfExists('edc_feed_part_stocks');
    }
}
