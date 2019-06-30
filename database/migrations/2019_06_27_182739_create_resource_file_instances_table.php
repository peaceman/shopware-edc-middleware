<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateResourceFileInstancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('resource_file_instances', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('file_id');
            $table->string('disk', 32);

            $table->timestamps();
            $table->timestamp('last_access_at')->nullable();

            $table->unique(['file_id', 'disk'], 'rfi_fi_di_uq');
            $table->foreign('file_id', 'rfi_rf_fk')
                ->references('id')->on('resource_files')
                ->onUpdate('cascade')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('resource_file_instances');
    }
}
