<?php
/** @var \Illuminate\Database\Eloquent\Factory $factory */

use Faker\Generator as Faker;

$factory->define(\App\Brand::class, function (Faker $faker) {
    return [
        'edc_brand_id' => $faker->numberBetween(),
        'brand_name' => $faker->company,
    ];
});

$factory->define(\App\BrandDiscount::class, function (Faker $faker) {
    return [
        'edc_feed_id' => function () {
            return factory(\App\EDCFeed::class)->create()->id;
        },
        'value' => $faker->numberBetween(23, 42),
    ];
});
