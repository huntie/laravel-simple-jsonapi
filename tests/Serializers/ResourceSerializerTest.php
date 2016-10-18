<?php

namespace Huntie\JsonApi\Tests\Serializers;

use Huntie\JsonApi\Serializers\ResourceSerializer;
use Huntie\JsonApi\Tests\TestCase;
use Huntie\JsonApi\Tests\Fixtures\Models\Post;
use Huntie\JsonApi\Tests\Fixtures\Models\User;
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
        $serializer = new ResourceSerializer($user, ['users' => ['name']]);
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
        $post = factory(Post::class)
            ->states('withAuthor', 'withComments')
            ->make();
        $serializer = new ResourceSerializer($post, [],  ['author', 'comments']);
        $resource = $serializer->toResourceObject();

        $this->assertArrayHasKey('relationships', $resource);
        $this->assertArrayHasKey('author', $resource['relationships']);
        $this->seeJsonApiResourceIdentifier($resource['relationships']['author']);
        $this->assertEquals('users', $resource['relationships']['author']['data']['type']);
        $this->assertArrayHasKey('comments', $resource['relationships']);
        $this->assertCount(2, $resource['relationships']['comments']['data']);
    }

    /**
     * Test loading of included records.
     */
    public function testIncludedRecords()
    {
        $user = factory(User::class)
            ->states('withPosts', 'withComments')
            ->make();
        $serializer = new ResourceSerializer($user, [], ['posts', 'comments']);
        $included = $serializer->getIncludedRecords();

        $this->assertInstanceOf(Collection::class, $included);
        $this->seeJsonApiObjectCollection(['data' => $included->toArray()], 4);

        foreach ($included as $record) {
            $this->assertRegExp('/posts|comments/', $record['type'], 'Unexpected record type included with resource');
        }
    }

    /**
     * Test scoping of included records.
     */
    public function testScopeIncludedRecords()
    {
        $user = factory(User::class)
            ->states('withPosts', 'withComments')
            ->make();
        $serializer = new ResourceSerializer($user, [], ['posts', 'comments']);
        $serializer->scopeIncludes(['posts']);
        $included = $serializer->getIncludedRecords();

        $this->assertInstanceOf(Collection::class, $included);
        $this->seeJsonApiObjectCollection(['data' => $included->toArray()], 2);

        foreach ($included as $record) {
            $this->assertEquals('posts', $record['type'], 'scopeIncludes() failed to return relationship subset');
        }
    }
}
