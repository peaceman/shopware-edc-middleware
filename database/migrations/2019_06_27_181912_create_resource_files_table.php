<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateResourceFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('resource_files', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('uuid', 36);
            $table->string('original_filename');
            $table->string('path');
            $table->unsignedBigInteger('size');
            $table->string('mime_type');
            $table->string('checksum', 32);

            $table->timestamps();
            $table->softDeletes();

            $table->unique('uuid', 'rf_uuid_uq');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('resource_files');
    }
}
