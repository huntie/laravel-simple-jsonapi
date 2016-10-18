<?php

namespace Huntie\JsonApi\Tests\Serializers;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Huntie\JsonApi\Serializers\CollectionSerializer;
use Huntie\JsonApi\Tests\TestCase;
use Huntie\JsonApi\Tests\Fixtures\Models\User;

class CollectionSerializerTest extends TestCase
{
    /**
     * Test the output of toResourceCollection.
     */
    public function testToResourceCollection()
    {
        $serializer = new CollectionSerializer(factory(User::class, 5)->make());

        $this->seeJsonApiObjectCollection($serializer->serializeToObject(), 5);
    }

    /**
     * Test the output of toResourceCollection with a paginated resource query.
     */
    public function testPaginatedCollection()
    {
        $pageSize = 5;
        $users = factory(User::class, 12)->make();
        $paginator = new LengthAwarePaginator($users->forPage(1, $pageSize), $users->count(), $pageSize);
        $paginator->setPath('http://localhost/users');

        $serializer = new CollectionSerializer($paginator);
        $document = $serializer->serializeToObject();

        $this->assertCount(5, $document['data'], 'Incorrect number of paginated records returned');

        $this->assertArrayHasKey('links', $document);
        $this->assertEquals([
            'first' => 'http://localhost/users?page=1',
            'last' => 'http://localhost/users?page=3',
            'prev' => null,
            'next' => 'http://localhost/users?page=2',
        ], $document['links']);

        $this->assertArrayHasKey('meta', $document);
        $this->assertEquals(12, $document['meta']['total']);
    }

    /**
     * Test loading of included records.
     */
    public function testIncludedRecords()
    {
        $users = factory(User::class, 3)
            ->states('withPosts')
            ->make();
        $serializer = new CollectionSerializer($users, [], ['posts']);
        $included = $serializer->getIncludedRecords();

        $this->assertInstanceOf(Collection::class, $included);
        $this->seeJsonApiObjectCollection(['data' => $included->toArray()], 6);

        foreach ($included as $record) {
            $this->assertEquals('posts', $record['type'], 'Unexpected record type included with collection');
        }
    }
}