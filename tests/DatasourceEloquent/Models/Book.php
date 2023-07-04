<?php

namespace ForestAdmin\AgentPHP\Tests\DatasourceEloquent\Models;

use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    protected $casts = [
        'published_at' => 'datetime:Y-m-d',
    ];

    public function bookReviews()
    {
        return $this->hasMany(BookReview::class);
    }

    public function author()
    {
        return $this->belongsTo(Author::class);
    }
}
