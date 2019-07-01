<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEdcFeedsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('edc_feeds', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('type');
            $table->unsignedBigInteger('resource_file_id');

            $table->foreign('resource_file_id', 'ef_rf_fk')
                ->references('id')->on('resource_files');

            $table->index('type', 'ef_type_idx');

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
        Schema::dropIfExists('edc_feeds');
    }
}
