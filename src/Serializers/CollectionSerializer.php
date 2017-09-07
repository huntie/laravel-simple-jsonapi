<?php

namespace Huntie\JsonApi\Serializers;

use Request;
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
     * The subset of attributes to return on each resource type.
     *
     * @var array
     */
    protected $fields;

    /**
     * The relationship paths to match for included resources.
     *
     * @var array
     */
    protected $include;

    /**
     * The additional named relationships to list against each resource.
     *
     * @var array
     */
    protected $relationships;

    /**
     * Create a new JSON API collection serializer.
     *
     * @param \Illuminate\Support\Collection|LengthAwarePaginator $records       The collection of records to serialise
     * @param array|null                                          $fields        The subset of fields to return on each resource type
     * @param array|null                                          $include       The paths of relationships to include
     * @param array|null                                          $relationships Additional named relationships to list
     */
    public function __construct($records, array $fields = [], array $include = [], array $relationships = [])
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
        $this->relationships = array_unique($relationships);
    }

    /**
     * Return a collection of JSON API resource objects for the record set.
     *
     * @return \Illuminate\Support\Collection
     */
    public function toResourceCollection()
    {
        return $this->records->map(function ($record) {
            return (new ResourceSerializer($record, $this->fields, $this->include, $this->relationships))
                ->toResourceObject();
        });
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
     * Return any secondary included resource objects.
     *
     * @throws \Huntie\JsonApi\Exceptions\InvalidRelationPathException
     *
     * @return \Illuminate\Support\Collection
     */
    public function getIncluded()
    {
        $included = collect();

        foreach ($this->records as $record) {
            $included = $included->merge(
                (new ResourceSerializer($record, $this->fields, $this->include, $this->relationships))
                    ->getIncluded()
            );
        }

        return $included->unique();
    }

    /**
     * Add pagination links and meta information to the main document.
     *
     * @param LengthAwarePaginator $paginator
     */
    protected function addPaginationLinks($paginator)
    {
        $this->addLinks(array_map(function ($page) use ($paginator) {
            return $this->formatPaginationQueryString(Request::query(), $page, $paginator->perPage());
        }, array_filter([
            'first' => 1,
            'last' => $paginator->lastPage(),
            'prev' => $paginator->currentPage() > 1 ? $paginator->currentPage() - 1 : null,
            'next' => $paginator->currentPage() < $paginator->lastPage() ? $paginator->currentPage() + 1 : null,
        ])));

        if (config('jsonapi.include_total_meta')) {
            $this->addMeta('total', $paginator->total());
        }
    }

    /**
     * Add JSON API pagination parameters to request query set based on
     * selected pagination strategy, and return the built URL query string.
     *
     * @param array $query
     * @param int   $number
     * @param int   $size
     */
    protected function formatPaginationQueryString(array $query = [], int $number, int $size): string
    {
        if (config('jsonapi.pagination_method') === 'offset-based') {
            $query['page'] = [
                'offset' => ($number - 1) * $size,
                'limit' => $size,
            ];
        }

        $query['page'] = compact('number', 'size');

        return '?' . urldecode(http_build_query($query));
    }
}
