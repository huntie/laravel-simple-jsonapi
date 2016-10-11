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

    /**
     * The posts the user has authored.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function post()
    {
        return $this->hasMany(Post::class, 'author_id');
    }
}
