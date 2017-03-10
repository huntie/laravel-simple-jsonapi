<?php

use Faker\Generator;
use Tests\Fixtures\Models\Comment;
use Tests\Fixtures\Models\User;

$factory->define(Comment::class, function (Generator $faker) {
    return [
        'id' => $faker->uuid,
        'title' => $faker->sentence(),
        'content' => $faker->paragraph(),
        'created_at' => $faker->dateTime(),
    ];
});
