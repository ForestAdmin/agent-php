<?php

namespace ForestAdmin\AgentPHP\Tests\DatasourceEloquent\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    public function bookReviews()
    {
        return $this->hasMany(BookReview::class);
    }
}
