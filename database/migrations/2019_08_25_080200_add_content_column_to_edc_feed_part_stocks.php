<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddContentColumnToEdcFeedPartStocks extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('edc_feed_part_stocks', function (Blueprint $table) {
            $table->text('content')->after('full_feed_id')->nullable(true);
            $table->unsignedBigInteger('file_id')->nullable(true)->change();
        });

        DB::statement(<<<EOT
alter table edc_feed_part_stocks
add content_checksum varchar(32) as (md5(content)) stored
after content;
EOT
);

        Schema::table('edc_feed_part_stocks', function (Blueprint $table) {
            $table->index('content_checksum', 'efps_cc_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // 😂
    }
}
