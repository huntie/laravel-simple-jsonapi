<?php

use Faker\Generator;
use Huntie\JsonApi\Tests\Fixtures\Models\Comment;
use Huntie\JsonApi\Tests\Fixtures\Models\User;

$factory->define(Comment::class, function (Generator $faker) {
    return [
        'id' => $faker->uuid,
        'title' => $faker->sentence(),
        'content' => $faker->paragraph(),
        'created_at' => $faker->dateTime(),
    ];
});
