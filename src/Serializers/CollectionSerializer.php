<?php

namespace Huntie\JsonApi\Serializers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CollectionSerializer extends JsonApiSerializer
{
    /**
     * The collection of records to transform.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $records;

    /**
     * The subset of attributes to return on each record type.
     *
     * @var array
     */
    protected $fields;

    /**
     * The relationships to load and include.
     *
     * @var array
     */
    protected $include;

    /**
     * Create a new JSON API collection serializer.
     *
     * @param \Illuminate\Support\Collection|LengthAwarePaginator $records The collection of records to serialize
     * @param array|null                                          $fields  Subset of fields to return by record type
     * @param array|null                                          $include Relations to include
     */
    public function __construct($records, array $fields = [], array $include = [])
    {
        parent::__construct();

        if ($records instanceof LengthAwarePaginator) {
            $this->addPaginationLinks($records);

            $this->records = $records->getCollection();
        } else {
            $this->records = $records;
        }

        $this->fields = array_unique($fields);
        $this->include = array_unique($include);
    }

    /**
     * Return a collection of JSON API resource objects for the record set.
     *
     * @return \Illuminate\Support\Collection
     */
    public function toResourceCollection()
    {
        return $this->records->map(function ($record) {
            return (new ResourceSerializer($record, $this->fields, $this->include))->toResourceObject();
        });
    }

    /**
     * Return a collection of JSON API resource objects for each included
     * relationship.
     *
     * @throws \Huntie\JsonApi\Exceptions\InvalidRelationPathException
     *
     * @return \Illuminate\Support\Collection
     */
    public function getIncludedRecords()
    {
        return $this->records->map(function ($record) {
            return (new ResourceSerializer($record, $this->fields, $this->include))->getIncludedRecords();
        })->flatten(1)->unique()->values();
    }

    /**
     * Return primary data for the JSON API document.
     *
     * @return mixed
     */
    protected function getPrimaryData()
    {
        return $this->toResourceCollection()->toArray();
    }

    /**
     * Return any secondary included resource data.
     *
     * @return array
     */
    protected function getIncludedData()
    {
        return $this->getIncludedRecords()->toArray();
    }

    /**
     * Add pagination links and meta information to the main document.
     *
     * @param LengthAwarePaginator $paginator
     */
    protected function addPaginationLinks($paginator)
    {
        $this->addLinks([
            'first' => $paginator->url(1),
            'last' => $paginator->url($paginator->lastPage()),
            'prev' => $paginator->previousPageUrl(),
            'next' => $paginator->nextPageUrl(),
        ]);

        $this->addMeta('total', $paginator->total());
    }
}
