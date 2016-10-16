<?php

use Faker\Generator;
use Huntie\JsonApi\Tests\Fixtures\Models\Comment;
use Huntie\JsonApi\Tests\Fixtures\Models\Post;
use Huntie\JsonApi\Tests\Fixtures\Models\User;

$factory->define(User::class, function (Generator $faker) {
    return [
        'id' => $faker->uuid,
        'name' => $faker->name,
        'email' => $faker->unique()->safeEmail,
        'password' => bcrypt('password'),
    ];
});

$factory->state(User::class, 'withPosts', function (Generator $faker) {
    return [
        'posts' => factory(Post::class, 2)->make(),
    ];
});

$factory->state(User::class, 'withComments', function (Generator $faker) {
    return [
        'comments' => factory(Comment::class, 2)->make(),
    ];
});
