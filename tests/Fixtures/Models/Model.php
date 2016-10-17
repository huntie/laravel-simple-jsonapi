<?php

namespace Huntie\JsonApi\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

class Model extends Eloquent
{
    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * Eager load relations on the model.
     *
     * @param array|string $relations
     *
     * @return $this
     */
    public function load($relationships)
    {
        return $this;
    }
}
