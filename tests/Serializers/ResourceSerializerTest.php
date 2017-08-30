<?php

namespace Tests\Serializers;

use Huntie\JsonApi\Serializers\ResourceSerializer;
use Tests\TestCase;
use Tests\Fixtures\Models\Comment;
use Tests\Fixtures\Models\Post;
use Tests\Fixtures\Models\User;
use Illuminate\Support\Collection;

class ResourceSerializerTest extends TestCase
{
    /**
     * Test the returned primary resource object type.
     */
    public function testPrimaryResourceType()
    {
        $serializer = new ResourceSerializer(factory(User::class)->make());
        $resource = $serializer->toResourceIdentifier();

        $this->assertEquals('users', $resource['type']);
    }

    /**
     * Test the output of toResourceIdentifier.
     */
    public function testToResourceIdentifier()
    {
        $serializer = new ResourceSerializer(factory(User::class)->make());
        $resource = $serializer->toResourceIdentifier();

        $this->assertInternalType('array', $resource);
        $this->assertCount(2, $resource);
        $this->assertArrayHasKey('type', $resource);
        $this->assertArrayHasKey('id', $resource);
    }

    /**
     * Test returned attributes in output of toBaseResourceObject.
     */
    public function testToBaseResourceObject()
    {
        $user = factory(User::class)->make();
        $serializer = new ResourceSerializer($user);
        $resource = $serializer->toBaseResourceObject();

        $this->assertInternalType('array', $resource);
        $this->assertArrayHasKey('attributes', $resource);
        $this->assertInternalType('array', $resource['attributes']);
        $this->assertArraySubset($resource['attributes'], $user->getAttributes());
        $this->assertArrayNotHasKey('id', $resource['attributes'], 'ID incorrectly included in attribute list');
        $this->assertArrayNotHasKey('password', $resource['attributes'], 'Hidden model field incorrectly included');
    }

    /**
     * Test returning a subset of attributes.
     */
    public function testFieldSubset()
    {
        $user = factory(User::class)->make();
        $serializer = new ResourceSerializer($user, ['users' => 'name']);
        $resource = $serializer->toBaseResourceObject();

        $this->assertArrayHasKey('name', $resource['attributes']);
        $this->assertArrayNotHasKey('email', $resource['attributes']);
    }

    /**
     * Test the set of grouped relationship identifiers returned with a full
     * resource object.
     */
    public function testResourceRelationships()
    {
        $post = factory(Post::class)->make();
        $post->author = factory(User::class)->make();
        $post->comments = factory(Comment::class, 2)->make();

        $serializer = new ResourceSerializer($post, [],  ['author', 'comments']);
        $resource = $serializer->toResourceObject();

        $this->assertArrayHasKey('relationships', $resource);
        $this->assertArrayHasKey('author', $resource['relationships']);
        $this->assertJsonApiResourceIdentifier($resource['relationships']['author']);
        $this->assertEquals('users', $resource['relationships']['author']['data']['type']);
        $this->assertArrayHasKey('comments', $resource['relationships']);
        $this->assertCount(2, $resource['relationships']['comments']['data']);
    }

    /**
     * Test loading of included records.
     */
    public function testIncludedRecords()
    {
        $user = factory(User::class)->make();
        $user->posts = factory(Post::class, 2)->make();
        $user->comments = factory(Comment::class, 2)->make();

        foreach ($user->comments as $comment) {
            $comment->creator = factory(User::class)->make();
        }

        $serializer = new ResourceSerializer($user, [], ['posts', 'comments', 'comments.creator']);
        $included = $serializer->getIncluded();

        $this->assertInstanceOf(Collection::class, $included);
        $this->assertJsonApiObjectCollection(['data' => $included->toArray()], 6);

        foreach ($included as $record) {
            $this->assertRegExp('/posts|comments|user/', $record['type'], 'Unexpected record type included with resource');
        }
    }

    /**
     * Test scoping of included records.
     */
    public function testScopeIncludedRecords()
    {
        $user = factory(User::class)->make();
        $user->posts = factory(Post::class, 2)->make();
        $user->comments = factory(Comment::class, 2)->make();

        $serializer = new ResourceSerializer($user, [], ['posts', 'comments']);
        $serializer->scopeIncludes(['posts']);
        $included = $serializer->getIncluded();

        $this->assertInstanceOf(Collection::class, $included);
        $this->assertCount(2, $included->toArray());

        foreach ($included as $record) {
            $this->assertEquals('posts', $record['type'], 'scopeIncludes() failed to return relationship subset');
        }
    }
}
