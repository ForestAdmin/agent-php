<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection;

use Closure;
use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\CollectionContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Utils\Schema as SchemaUtils;
use Illuminate\Support\Collection as IlluminateCollection;
use Illuminate\Support\Str;

class Projection extends IlluminateCollection
{
    public function columns(): array
    {
        $columns = $this->filter(fn ($column) => ! Str::contains($column, ':'));

        return $columns->all();
    }

    public function relations(): IlluminateCollection
    {
        return collect($this->reduce(
            static function ($memo, $path) {
                if (Str::contains($path, ':')) {
                    $relation = Str::before($path, ':');
                    $memo[$relation] = new Projection(
                        [
                            ...($memo[$relation] ?? []),
                            Str::after($path, ':'),
                        ]
                    );
                }

                return $memo;
            },
            []
        ));
    }

    public function replaceItem(Closure $callback): Projection
    {
        return $this
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
                } else {
                    return [...$memo, ...[$items]];
                }
            }, []);

        return new Projection(array_values(array_unique($fields)));
    }

    public function apply(array $records): IlluminateCollection
    {
        return collect($records)->map(fn ($record) => $this->reproject($record));
    }

    public function withPks(CollectionContract $collection): Projection
    {
        foreach (SchemaUtils::getPrimaryKeys($collection) as $primaryKey) {
            if (! $this->contains($primaryKey)) {
                $this->push($primaryKey);
            }
        }

        foreach ($this->relations() as $relation => $projection) {
            $schema = $collection->getFields()[$relation];
            $association = AgentFactory::get('datasource')->getCollection($schema->getForeignCollection());
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
     * @throws ForestException
     */
    public function unnest(): Projection
    {
        $prefix = Str::before(collect($this)->first(), ':');

        if (! collect($this)->every(fn ($path) => Str::startsWith($path, $prefix))) {
            throw new ForestException('Cannot unnest projection.');
        }

        return new Projection(collect($this)->map(fn ($path) => Str::after($path, "$prefix:"))->toArray());
    }

    private function reproject($record = null)
    {
        $result = null;

        if ($record) {
            $result = [];

            foreach ($this->columns() as $column) {
                $result[$column] = $record[$column];
            }
            foreach ($this->relations() as $relation => $projection) {
                $result[$relation] = $projection->reproject($record[$relation]);
            }
        }

        return $result;
    }
}
