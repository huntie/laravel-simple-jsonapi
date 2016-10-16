<?php

use Faker\Generator;
use Huntie\JsonApi\Tests\Fixtures\Models\Post;
use Huntie\JsonApi\Tests\Fixtures\Models\User;

$factory->define(Post::class, function (Generator $faker) {
    return [
        'id' => $faker->uuid,
        'title' => $faker->sentence(),
        'content' => $faker->paragraphs(4),
        'created_at' => $faker->dateTime(),
    ];
});
