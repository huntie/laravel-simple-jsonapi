<?php

namespace Huntie\JsonApi\Tests\Serializers;

use Huntie\JsonApi\Serializers\ResourceSerializer;
use Huntie\JsonApi\Tests\TestCase;
use Huntie\JsonApi\Tests\Fixtures\Models\User;

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
}
