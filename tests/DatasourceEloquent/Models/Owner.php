<?php

namespace ForestAdmin\AgentPHP\Tests\DatasourceEloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Owner extends Model
{
    public function cars(): BelongsToMany
    {
        return $this->belongsToMany(Car::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
