<?php

namespace Tests\Fixtures\Models;

class Tag extends Model
{
    /**
     * The posts matching the tag.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function posts()
    {
        return $this->belongsToMany(Post::class, 'post_tags');
    }
}
