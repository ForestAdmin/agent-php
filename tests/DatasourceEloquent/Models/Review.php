<?php

namespace ForestAdmin\AgentPHP\Tests\DatasourceEloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Review extends Model
{
    public function bookReviews()
    {
        return $this->hasMany(BookReview::class);
    }

    public function books(): BelongsToMany
    {
        return $this->belongsToMany(Book::class);
    }
}
