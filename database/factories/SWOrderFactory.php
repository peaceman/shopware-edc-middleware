<?php
/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\SWOrder;
use Faker\Generator as Faker;

$factory->define(SWOrder::class, function (Faker $faker) {
    return [
        'sw_order_number' => $faker->ean13,
    ];
});
