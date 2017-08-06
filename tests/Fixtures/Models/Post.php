<?php

namespace Tests\Fixtures\Models;

use Huntie\JsonApi\Contracts\Model\IncludesRelatedResources;

class Post extends Model implements IncludesRelatedResources
{
    /**
     * The relationships which can be included with this resource.
     *
     * @return array
     */
    public function getIncludableRelations()
    {
        return [
            'author',
            'comments',
            'tags',
        ];
    }

    /**
     * The author of the post.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * The comments made in reply to the post.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * The post tag memberships.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'post_tags');
    }
}
