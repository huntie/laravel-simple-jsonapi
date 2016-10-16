<?php

namespace Huntie\JsonApi\Tests\Serializers;

use Huntie\JsonApi\Serializers\ResourceSerializer;
use Huntie\JsonApi\Tests\TestCase;
use Huntie\JsonApi\Tests\Fixtures\Models\User;

class JsonApiSerializerTest extends TestCase
{
    /**
     * Test the base output of serializeToObject.
     */
    public function testSerializeToObject()
    {
        $serializer = new ResourceSerializer(factory(User::class)->make());
        $document = $serializer->serializeToObject();

        $this->assertInternalType('array', $document);
        $this->seeJsonApiResourceObject($document);
    }

    /**
     * Test the base output of serializeToJson.
     */
    public function testSerializeToJson()
    {
        $serializer = new ResourceSerializer(factory(User::class)->make());
        $document = $serializer->serializeToJson();

        $this->assertInternalType('string', $document);
        $this->assertEquals($document, json_encode($serializer->serializeToObject()));
    }

    /**
     * Test output of serializer using JsonSerializable interface.
     */
    public function testJsonSerializable()
    {
        $serializer = new ResourceSerializer(factory(User::class)->make());

        $this->assertEquals($serializer->serializeToJson(), json_encode($serializer));
    }

    /**
     * Test adding top-level meta information to the document.
     */
    public function testAddMeta()
    {
        $serializer = new ResourceSerializer(factory(User::class)->make());
        $serializer->addMeta('is_admin', true);
        $document = $serializer->serializeToObject();

        $this->assertArrayHasKey('meta', $document);
        $this->assertCount(1, $document['meta']);
        $this->assertEquals(true, $document['meta']['is_admin']);
    }

    /**
     * Test adding top-level resource links to the document.
     */
    public function testAddLinks()
    {
        $serializer = new ResourceSerializer(factory(User::class)->make());
        $serializer->addLinks([
            'next' => 'http://localhost/users/3',
            'prev' => 'http://localhost/users/1',
        ]);
        $document = $serializer->serializeToObject();

        $this->assertArrayHasKey('links', $document);
        $this->assertCount(2, $document['links']);
        $this->assertEquals('http://localhost/users/3', $document['links']['next']);
        $this->assertEquals('http://localhost/users/1', $document['links']['prev']);
    }

    /**
     * Test the top-level 'jsonapi' meta object when enabled.
     */
    public function testDocumentMeta()
    {
        $this->app['config']->set('jsonapi.include_version', true);

        $serializer = new ResourceSerializer(factory(User::class)->make());
        $document = $serializer->serializeToObject();

        $this->assertArrayHasKey('jsonapi', $document);
        $this->assertArrayHasKey('version', $document['jsonapi']);
    }
}
