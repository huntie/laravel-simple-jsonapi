<?php

namespace Tests\Serializers;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Huntie\JsonApi\Serializers\CollectionSerializer;
use Tests\TestCase;
use Tests\Fixtures\Models\Post;
use Tests\Fixtures\Models\User;

class CollectionSerializerTest extends TestCase
{
    /**
     * Test the output of toResourceCollection.
     */
    public function testToResourceCollection()
    {
        $serializer = new CollectionSerializer(factory(User::class, 5)->make());

        $this->assertJsonApiObjectCollection($serializer->serializeToObject(), 5);
    }

    /**
     * Test the output of toResourceCollection with a paginated resource query.
     */
    public function testPaginatedCollection()
    {
        $pageSize = 5;
        $users = factory(User::class, 12)->make();
        $paginator = new LengthAwarePaginator($users->forPage(1, $pageSize), $users->count(), $pageSize);

        $serializer = new CollectionSerializer($paginator);
        $serializer->setBaseUrl('http://localhost/users');
        $document = $serializer->serializeToObject();

        $this->assertCount(5, $document['data'], 'Incorrect number of paginated records returned');

        $this->assertArrayHasKey('links', $document);
        $this->assertEquals([
            'first' => 'http://localhost/users?page[number]=1&page[size]=5',
            'last' => 'http://localhost/users?page[number]=3&page[size]=5',
            'next' => 'http://localhost/users?page[number]=2&page[size]=5',
            'self' => 'http://localhost/users'
        ], $document['links']);
    }

    /**
     * Test for correct meta information when 'include_total_meta' is set.
     */
    public function testPaginatedCollectionWithTotal()
    {
        $this->app['config']->set('jsonapi.include_total_meta', true);

        $users = factory(User::class, 5)->make();
        $paginator = new LengthAwarePaginator($users->forPage(1, 2), $users->count(), 2);
        $document = (new CollectionSerializer($paginator))->serializeToObject();

        $this->assertArrayHasKey('meta', $document);
        $this->assertEquals($users->count(), $document['meta']['total']);
    }

    /**
     * Test loading of included records.
     */
    public function testIncludedRecords()
    {
        $users = factory(User::class, 3)
            ->make()
            ->map(function ($user) {
                $user->posts = factory(Post::class, 2)->make();

                return $user;
            });

        $serializer = new CollectionSerializer($users, [], ['posts']);
        $included = $serializer->getIncluded();

        $this->assertInstanceOf(Collection::class, $included);
        $this->assertJsonApiObjectCollection(['data' => $included->toArray()], 6);

        foreach ($included as $record) {
            $this->assertEquals('posts', $record['type'], 'Unexpected record type included with collection');
        }
    }
}
