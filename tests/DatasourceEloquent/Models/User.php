<?php

namespace ForestAdmin\AgentPHP\Tests\DatasourceEloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class User extends Model
{
    public function owner(): HasOne
    {
        return $this->hasOne(Owner::class);
    }

    public function comment(): MorphOne
    {
        return $this->morphOne(Comment::class, 'commentable');
    }
}
