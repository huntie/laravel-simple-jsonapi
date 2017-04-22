<?php

use Faker\Generator;
use Tests\Fixtures\Models\Comment;
use Tests\Fixtures\Models\Post;
use Tests\Fixtures\Models\User;

$factory->define(Post::class, function (Generator $faker) {
    return [
        'id' => $faker->uuid,
        'title' => $faker->sentence(),
        'content' => $faker->paragraphs(4),
        'created_at' => $faker->dateTime(),
    ];
});
