<?php

namespace ForestAdmin\AgentPHP\Tests\DatasourceEloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Model
{
    public function owner(): HasOne
    {
        return $this->hasOne(Owner::class);
    }
}
