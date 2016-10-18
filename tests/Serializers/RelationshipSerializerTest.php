<?php

namespace Huntie\JsonApi\Tests\Serializers;

use Huntie\JsonApi\Serializers\RelationshipSerializer;
use Huntie\JsonApi\Tests\TestCase;
use Huntie\JsonApi\Tests\Fixtures\Models\Post;
use Huntie\JsonApi\Tests\Fixtures\Models\User;

class RelationshipSerializerTest extends TestCase
{
    /**
     * Test the output of toResourceLinkage on a has one relationship.
     */
    public function testHasOneToResourceLinkage()
    {
        $post = factory(Post::class)
            ->states('withAuthor')
            ->make();
        $serializer = new RelationshipSerializer($post, 'author');
        $relationship = $serializer->toResourceLinkage();

        $this->seeJsonApiResourceIdentifier(['data' => $relationship]);
        $this->assertEquals([
            'type' => 'users',
            'id' => $post->author->id,
        ], $relationship);
    }

    /**
     * Test the output of toResourceLinkage on a has many relationship.
     */
    public function testHasManyToResourceLinkage()
    {
        $user = factory(User::class)
            ->states('withPosts')
            ->make();
        $serializer = new RelationshipSerializer($user, 'posts');
        $relationship = $serializer->toResourceLinkage();

        $this->seeJsonApiIdentifierCollection(['data' => $relationship->toArray()], 2);

        foreach ($relationship as $identifier) {
            $this->assertEquals('posts', $identifier['type']);
        }
    }

    /**
     * Test the output of toResourceCollection on a has one relationship.
     */
    public function testHasOneToResourceCollection()
    {
        $post = factory(Post::class)
            ->states('withAuthor')
            ->make();
        $serializer = new RelationshipSerializer($post, 'author');
        $relationship = $serializer->toResourceCollection();

        $this->seeJsonApiResourceObject(['data' => $relationship]);
        $this->assertEquals('users', $relationship['type']);
    }

    /**
     * Test the output of toResourceCollection on a has many relationship.
     */
    public function testHasManyToResourceCollection()
    {
        $user = factory(User::class)
            ->states('withPosts')
            ->make();
        $serializer = new RelationshipSerializer($user, 'posts');
        $relationship = $serializer->toResourceCollection();

        $this->seeJsonApiObjectCollection(['data' => $relationship->toArray()], 2);

        foreach ($relationship as $identifier) {
            $this->assertEquals('posts', $identifier['type']);
        }
    }

    /**
     * Test returning a subset of attributes from toResourceCollection.
     */
    public function testCollectionFieldSubset()
    {
        $user = factory(User::class)
            ->states('withPosts')
            ->make();
        $serializer = new RelationshipSerializer($user, 'posts', [
            'posts' => ['title', 'created_at']
        ]);

        foreach ($serializer->toResourceCollection() as $record) {
            $this->assertArrayHasKey('title', $record['attributes']);
            $this->assertArrayHasKey('created_at', $record['attributes']);
            $this->assertArrayNotHasKey('content', $record['attributes']);
        }
    }
}
