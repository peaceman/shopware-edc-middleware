<?php
/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\ResourceFile\ResourceFile;
use Faker\Generator as Faker;

$factory->define(ResourceFile::class, function (Faker $faker) {
    $filename = $faker->slug . ".{$faker->fileExtension}";
    return [
        'uuid' => $faker->uuid,
        'original_filename' => $filename,
        'path' => "files/$filename",
        'size' => $faker->numberBetween(),
        'mime_type' => $faker->mimeType,
        'checksum' => $faker->md5,
    ];
});
