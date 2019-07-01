<?php
/** @var \Illuminate\Database\Eloquent\Factory $factory */

use Faker\Generator as Faker;

$factory->define(\App\EDCFeed::class, function (Faker $faker) {
    return [
        'type' => 'foo the bar',
        'resource_file_id' => factory(\App\ResourceFile\ResourceFile::class)->create()->id,
    ];
});
