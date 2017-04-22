<?php

namespace Tests\Serializers;

use Huntie\JsonApi\Serializers\RelationshipSerializer;
use Tests\TestCase;
use Tests\Fixtures\Models\Post;
use Tests\Fixtures\Models\User;

class RelationshipSerializerTest extends TestCase
{
    /**
     * Test the output of toResourceLinkage on a has one relationship.
     */
    public function testHasOneToResourceLinkage()
    {
        $post = factory(Post::class)->make();
        $post->author = factory(User::class)->make();

        $serializer = new RelationshipSerializer($post, 'author');
        $relationship = $serializer->toResourceLinkage();

        $this->assertJsonApiResourceIdentifier(['data' => $relationship]);
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
        $user = factory(User::class)->make();
        $user->posts = factory(Post::class, 2)->make();

        $serializer = new RelationshipSerializer($user, 'posts');
        $relationship = $serializer->toResourceLinkage();

        $this->assertJsonApiIdentifierCollection(['data' => $relationship->toArray()], 2);

        foreach ($relationship as $identifier) {
            $this->assertEquals('posts', $identifier['type']);
        }
    }

    /**
     * Test the output of toResourceCollection on a has one relationship.
     */
    public function testHasOneToResourceCollection()
    {
        $post = factory(Post::class)->make();
        $post->author = factory(User::class)->make();

        $serializer = new RelationshipSerializer($post, 'author');
        $relationship = $serializer->toResourceCollection();

        $this->assertJsonApiResourceObject(['data' => $relationship]);
        $this->assertEquals('users', $relationship['type']);
    }

    /**
     * Test the output of toResourceCollection on a has many relationship.
     */
    public function testHasManyToResourceCollection()
    {
        $user = factory(User::class)->make();
        $user->posts = factory(Post::class, 2)->make();

        $serializer = new RelationshipSerializer($user, 'posts');
        $relationship = $serializer->toResourceCollection();

        $this->assertJsonApiObjectCollection(['data' => $relationship->toArray()], 2);

        foreach ($relationship as $identifier) {
            $this->assertEquals('posts', $identifier['type']);
        }
    }

    /**
     * Test returning a subset of attributes from toResourceCollection.
     */
    public function testCollectionFieldSubset()
    {
        $user = factory(User::class)->make();
        $user->posts = factory(Post::class, 2)->make();

        $serializer = new RelationshipSerializer($user, 'posts', [
            'posts' => ['title', 'created_at']
        ]);

        foreach ($serializer->toResourceCollection() as $record) {
            $this->assertArrayHasKey('title', $record['attributes']);
            $this->assertArrayHasKey('created_at', $record['attributes']);
            $this->assertArrayNotHasKey('content', $record['attributes']);
        }
    }

    /**
     * Test resolving an invalid relation path.
     *
     * @expectedException \Huntie\JsonApi\Exceptions\InvalidRelationPathException
     */
    public function testInvalidRelationPath()
    {
        $user = factory(User::class)->make();

        $serializer = new RelationshipSerializer($user, 'nonexistent.relation');
        $serializer->toResourceLinkage();
    }
}
