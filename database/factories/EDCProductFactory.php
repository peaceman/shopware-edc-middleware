<?php
/** @var \Illuminate\Database\Eloquent\Factory $factory */

use Faker\Generator as Faker;

$factory->define(\App\EDCProduct::class, function (Faker $faker) {
    return [
        'brand_id' => function () {
            return factory(\App\Brand::class)->create()->id;
        },
        'edc_id' => $faker->unique()->numberBetween()
    ];
});

$factory->define(\App\EDCProductVariant::class, function (Faker $faker) {
    return [
        'product_id' => function () {
            return factory(\App\EDCProduct::class)->create()->id;
        },
        'edc_id' => $faker->unique()->numberBetween()
    ];
});

$factory->define(\App\EDCProductVariantData::class, function (Faker $faker) {
    return [
        'feed_part_product_id' => function () {
            return factory(\App\EDCFeedPartProduct::class)->create()->id;
        },
        'subartnr' => $faker->unique()->ean13,
    ];
});

$factory->afterCreating(\App\EDCProductVariant::class, function (\App\EDCProductVariant $variant, Faker $faker) {
    $variant->saveData(factory(\App\EDCProductVariantData::class)->make());
});
