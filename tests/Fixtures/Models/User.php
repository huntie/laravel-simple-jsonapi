<?php

namespace Huntie\JsonApi\Tests\Fixtures\Models;

use Huntie\JsonApi\Contracts\Model\IncludesRelatedResources;

class User extends Model implements IncludesRelatedResources
{
    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['password'];

    /**
     * The relationships which can be included with this resource.
     *
     * @return array
     */
    public function getIncludableRelations()
    {
        return [
            'posts',
            'comments',
        ];
    }

    /**
     * The posts the user has authored.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function posts()
    {
        return $this->hasMany(Post::class, 'author_id');
    }

    /**
     * The post comments the user has created.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function comments()
    {
        return $this->hasMany(Comment::class, 'creator_id');
    }
}
