<?php

namespace Huntie\JsonApi\Tests\Fixtures\Models;

class User extends Model
{
    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['password'];
}
