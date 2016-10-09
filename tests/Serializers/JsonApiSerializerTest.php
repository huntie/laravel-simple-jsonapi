<?php

namespace Huntie\JsonApi\Tests\Serializers;

use Huntie\JsonApi\Serializers\ResourceSerializer;
use Huntie\JsonApi\Tests\TestCase;
use Huntie\JsonApi\Tests\Fixtures\Models\User;

class JsonApiSerializerTest extends TestCase
{
    /**
     * Test the base output of serializeToJson.
     */
    public function testSerializeToJson()
    {
        $serializer = new ResourceSerializer(factory(User::class)->make());
        $json = $serializer->serializeToJson();

        $this->assertInternalType('string', $json);
        $this->assertEquals($json, json_encode($serializer->serializeToObject()));
    }

    /**
     * Test output of serializer using JsonSerializable interface.
     */
    public function testJsonSerializable()
    {
        $serializer = new ResourceSerializer(factory(User::class)->make());

        $this->assertEquals($serializer->serializeToJson(), json_encode($serializer));
    }
}
