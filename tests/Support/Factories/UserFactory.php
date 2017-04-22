<?php

use Faker\Generator;
use Tests\Fixtures\Models\Comment;
use Tests\Fixtures\Models\Post;
use Tests\Fixtures\Models\User;

$factory->define(User::class, function (Generator $faker) {
    return [
        'id' => $faker->uuid,
        'name' => $faker->name,
        'email' => $faker->unique()->safeEmail,
        'password' => bcrypt('password'),
    ];
});
