<?php

namespace ForestAdmin\AgentPHP\Tests\DatasourceEloquent\Models;

use Illuminate\Database\Eloquent\Model;

class BookReview extends Model
{
    protected $table = 'book_review';

    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    public function review()
    {
        return $this->belongsTo(Review::class);
    }
}
