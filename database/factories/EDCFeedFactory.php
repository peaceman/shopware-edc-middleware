<?php
/** @var \Illuminate\Database\Eloquent\Factory $factory */

use Faker\Generator as Faker;

$factory->define(\App\EDCFeed::class, function (Faker $faker) {
    return [
        'type' => 'foo the bar',
        'resource_file_id' => factory(\App\ResourceFile\ResourceFile::class)->create()->id,
    ];
});

$factory->define(\App\EDCFeedPartProduct::class, function (Faker $faker) {
    return [
        'file_id' => function () {
            return factory(\App\ResourceFile\ResourceFile::class)->create()->id;
        },
        'full_feed_id' => function () {
            return factory(\App\EDCFeed::class)->create()->id;
        }
    ];
});
