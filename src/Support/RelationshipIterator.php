<?php

namespace Huntie\JsonApi\Support;

use InvalidArgumentException;
use Huntie\JsonApi\Exceptions\InvalidRelationPathException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

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
    public function __construct($record, string $path)
    {
        if (!preg_match('/^([A-Za-z]+.?)+[A-Za-z]+$/', $path)) {
            throw new InvalidArgumentException('The relationship path must be a valid string');
        }

        $this->record = $record;
        $this->path = $path;
    }

    /**
     * Resolve the relationship from the primary record.
     *
     * @return Collection|Model|null
     */
    public function resolve()
    {
        return $this->iterate($this->record, $this->path);
    }

    /**
     * Recursively iterate through a given relation path to return the resolved
     * relationship value.
     *
     * @param Collection|Model|null $resolved
     * @param string|null           $path
     *
     * @return Collection|Model|null
     */
    private function iterate($resolved, $path)
    {
        if (empty($path)) {
            return $resolved;
        }

        $relation = null;
        [$relation, $path] = array_pad(explode('.', $path, 2), 2, null);

        $resolved = $resolved->{$relation};

        if ($resolved instanceof Collection) {
            return $resolved->map(function ($record) use ($path) {
                return $this->iterate($record, $path);
            });
        }

        if (!$resolved instanceof Model || in_array($relation, $resolved->getHidden())) {
            throw new InvalidRelationPathException($this->path);
        }

        return $this->iterate($resolved, $path);
    }
}
