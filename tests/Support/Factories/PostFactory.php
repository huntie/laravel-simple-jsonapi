<?php

use Faker\Generator;
use Tests\Fixtures\Models\Comment;
use Tests\Fixtures\Models\Post;
use Tests\Fixtures\Models\User;

$factory->define(Post::class, function (Generator $faker) {
    return [
        'id' => $faker->uuid,
        'title' => $faker->sentence(),
        'content' => '<p>' . implode('</p><p>', $faker->paragraphs(4)) . '</p>',
        'created_at' => $faker->dateTime(),
    ];
});
