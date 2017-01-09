<?php

namespace Huntie\JsonApi\Support;

use InvalidArgumentException;
use Huntie\JsonApi\Contracts\JsonApiResource;
use Huntie\JsonApi\Exceptions\InvalidRelationPathException;
use Illuminate\Database\Eloquent\Model;

class RelationshipIterator
{
    /**
     * The primary record.
     */
    private $record;

    /**
     * The path to the relation.
     *
     * @var string
     */
    private $path;

    /**
     * Create a new RelationshipIterator instance.
     *
     * @param Model  $record The primary record
     * @param string $path   The path to the relation
     *
     * @throws InvalidArgumentException
     */
    public function __construct($record, $path)
    {
        if (!preg_match('/^([A-Za-z]+.?)+[A-Za-z]+$/', $path)) {
            throw new InvalidArgumentException('The relationship path must be a valid string');
        }

        if (!$record instanceof JsonApiResource || !in_array($path, $record->getIncludableRelations())) {
            throw new InvalidRelationPathException($path);
        }

        $this->record = $record;
        $this->path = $path;
    }

    /**
     * Resolve the relationship from the primary record.
     *
     * @throws InvalidRelationPathException
     *
     * @return Collection|Model|null
     */
    public function resolve()
    {
        return array_reduce(explode('.', $this->path), function ($resolved, $relation) {
            if (!$resolved instanceof Model || in_array($relation, $resolved->getHidden())) {
                throw new InvalidRelationPathException($this->path);
            }

            return $resolved->{$relation};
        }, $this->record);
    }
}
