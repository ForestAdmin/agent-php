<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection;

use ArrayObject;
use Closure;
use Exception;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Utils\Schema as SchemaUtils;
use Illuminate\Support\Collection as IlluminateCollection;
use Illuminate\Support\Str;
use function ForestAdmin\cache;

class Projection extends IlluminateCollection
{
    public function columns(): array
    {
        $columns = $this->filter(fn ($column) => ! Str::contains($column, ':'));

        return $columns->all();
    }

    public function relations()
    {
        return $this->reduce(
                static function ($memo, $path) {
                    if (Str::contains($path, ':')) {
                        $relation = Str::before($path, ':');
                        $memo[$relation] = new Projection(
                            [
                                ...($memo[$relation] ?? []),
                                Str::after($path, ':')
                            ]
                        );
                    }

                    return $memo;
                },
                []
            );
    }

    public function replaceItem(Closure $callback)
    {
        return collect($this)
            ->map($callback)
            ->reduce(
                static function (Projection $memo, $path) {
                    if (is_string($path)) {
                        return $memo->union([$path]);
                    }

                    return $memo->union($path);
                },
                new Projection()
            );
    }

    public function union($items): Projection
    {
        $fields = $this->merge($items)
            ->reduce(function ($memo, $items) {
                if (is_iterable($items)) {
                    return [...$memo, ...$items];
                } elseif ($items !== null) {
                    return [...$memo, ...[$items]];
                } else {
                    return $memo;
                }
            }, []);

        return new Projection(array_unique($fields));
    }

    public function apply(array $records): IlluminateCollection
    {
        return collect($records)->map(fn($record) => $this->reproject($record));
    }

    public function withPks(Collection $collection): Projection
    {
        foreach (SchemaUtils::getPrimaryKeys($collection) as $primaryKey) {
            if (! $this->contains($primaryKey)) {
                $this->push($primaryKey);
            }
        }

        foreach ($this->relations() as $relation => $projection) {
            $schema = $collection->getFields()[$relation];
            $association = cache('datasource')->getCollection($schema->getForeignCollection());
            $projectionWithPk = $projection->withPks($association)->nest($relation);

            foreach ($projectionWithPk as $field) {
                if (! $this->contains($field)) {
                    $this->push($field);
                }
            }
        }

        return $this;
    }

    public function nest(?string $prefix = null): Projection
    {
        return $prefix ? new Projection((collect($this)->map(fn ($path) => "$prefix:$path"))->toArray()) : $this;
    }

    /**
     * @throws Exception
     */
    public function unnest(): Projection
    {
        $prefix = Str::before(collect($this)->first(), ':');

        if (! collect($this)->every(fn ($path) => Str::startsWith($path, $prefix))) {
            throw new Exception('Cannot unnest projection.');
        }

        return new Projection(collect($this)->map(fn ($path) => Str::after($path, "$prefix:"))->toArray());
    }

    private function reproject($record = null)
    {
        $result = null;

        if ($record) {
            $result = [];


            foreach($this->columns() as $column) {
                $result[$column] = $record[$column];
            }
            foreach($this->relations() as $relation => $projection) {
                $result[$relation] = $projection->reproject($record[$relation]);
            }
        }

        return $result;
    }
}
