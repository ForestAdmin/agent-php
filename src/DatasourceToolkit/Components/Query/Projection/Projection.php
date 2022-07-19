<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection;

use Closure;
use Exception;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Utils\Schema as SchemaUtils;
use Illuminate\Support\Collection as IlluminateCollection;
use Illuminate\Support\Str;
use function ForestAdmin\cache;

class Projection
{
    private IlluminateCollection $fields;

    public function __construct(array $array = [])
    {
        $this->fields = collect($array);
    }

    /**
     * @return IlluminateCollection
     */
    public function getFields(): IlluminateCollection
    {
        return $this->fields;
    }

    public function columns(): IlluminateCollection
    {
        $columns = $this->fields->filter(fn ($column) => ! Str::contains($column, ':'));

        return $columns;
    }

    public function relations()
    {
        return $this->fields
            ->reduce(
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

    public function replace(Closure $callback)
    {
        return $this->fields
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

    public function union(Projection|array $projection): Projection
    {
        $fields = collect([...$this->fields, $projection->getFields()])
            ->reduce(fn ($memo, $projection) => $projection ? [...$memo, ...$projection] : $memo);

        return new Projection($fields);
    }

    public function apply(IlluminateCollection $records): IlluminateCollection
    {
        return $records->map(fn($record) => $this->reproject($record));
    }

    public function withPks(Collection $collection): Projection
    {
        $result = new Projection($this->fields->toArray());

        foreach (SchemaUtils::getPrimaryKeys($collection) as $primaryKey) {
            if (!$result->fields->contains($primaryKey)) {
                $result->fields->push($primaryKey);
            }
        }

        foreach ($this->relations() as $relation) {
            $schema = $collection->getFields()[$relation];
            $association = cache('datasource')->getCollection($schema->getForeignCollection());
            $projectionWithPk = $this->withPks($association)->nest($relation);

            foreach ($projectionWithPk as $field) {
                if (!$result->fields->contains($field)) {
                    $result->fields->push($field);
                }
            }
        }

        return $result;
    }

    public function nest(?string $prefix = null): Projection
    {
        return $prefix ? ($this->fields->map(fn ($path) => "$prefix:$path")) : $this;
    }

    /**
     * @throws Exception
     */
    public function unnest(): Projection
    {
        $prefix = Str::before($this->fields->first(), ':');

        if (! $this->fields->every(fn ($path) => Str::startsWith($path, $prefix))) {
            throw new Exception('Cannot unnest projection.');
        }

        return $this->fields->map(fn ($path) => Str::after($path, $prefix));
    }

    private function reproject($record = null)
    {
        $result = null;

        if ($record) {
            $result = [];

            foreach($this->columns() as $column) {
                $result[$column] = $record[$column];
            }

            foreach($this->relations() as $relation) {
                $result[$relation] = $this->reproject($record[$relation]);
            }
        }

        return $result;
    }
}
