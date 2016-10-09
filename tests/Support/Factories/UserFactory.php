<?php

use Faker\Generator;
use Huntie\JsonApi\Tests\Fixtures\Models\User;

$factory->define(User::class, function (Generator $faker) {
    return [
        'name' => $faker->name,
        'email' => $faker->unique()->safeEmail,
        'password' => bcrypt('password'),
    ];
});
