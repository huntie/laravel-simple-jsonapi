<?php

use Faker\Generator;
use Huntie\JsonApi\Tests\Fixtures\Models\Comment;
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

$factory->state(Post::class, 'withAuthor', function (Generator $faker) {
    return [
        'author' => factory(User::class)->make(),
    ];
});

$factory->state(Post::class, 'withComments', function (Generator $faker) {
    return [
        'comments' => factory(Comment::class, 2)->make(),
    ];
});
