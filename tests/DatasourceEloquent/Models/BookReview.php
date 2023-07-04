<?php

namespace ForestAdmin\AgentPHP\Tests\DatasourceEloquent\Models;

use Illuminate\Database\Eloquent\Model;

class BookReview extends Model
{
    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    public function review()
    {
        return $this->belongsTo(Review::class);
    }
}
