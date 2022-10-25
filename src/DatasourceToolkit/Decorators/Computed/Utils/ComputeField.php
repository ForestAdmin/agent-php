<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Computed\Utils;

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Computed\ComputedCollection;
use Illuminate\Support\Str;

class ComputeField
{
    public static function computeFromRecords(
        ComputedCollection $collection,
        Projection         $recordsProjection,
        Projection         $desiredProjection,
        array              $records
    ) {
        $paths = clone $recordsProjection;
        $flatten = Flattener::flatten($records, $paths);

        $desiredProjection->each(fn ($path) => self::queueField($collection, $path, $paths, $flatten));

        return Flattener::unFlatten(
            $desiredProjection->map(fn ($path) => $flatten[$paths->search($path)]),
            $desiredProjection
        );
    }

    public static function queueField(
        ComputedCollection $collection,
        string             $newPath,
        Projection         $paths,
        array              &$flatten
    ): void {
        if (! $paths->contains($newPath)) {
            $computed = $collection->getComputed($newPath);
            $nestedDependencies = (new Projection($computed->getDependencies()))
                ->nest(Str::contains($newPath, ':') ? Str::before($newPath, ':') : null);

            $nestedDependencies->each(fn ($path) => self::queueField($collection, $path, $paths, $flatten));
            $dependencyValues = $nestedDependencies->map(fn ($path) => $flatten[$paths->search($path)]);
            $paths->push($newPath);
//            promises.push(computeField(ctx, computed, computed.dependencies, dependencyValues));
            $flatten[] = self::computeField($computed, $computed->getDependencies(), $dependencyValues);
        }
    }

    public static function computeField(
        ComputedDefinition $computedDefinition,
        array              $computedDependencies,
        array              &$flatten
    ): array {
        // todo
    }

    public static function transformUniqueValues(
        array    $inputs,
        \Closure $callback
    ): array {
        $indexes = [];
        $mapping = [];
        $uniqueInputs = [];

        foreach ($inputs as $input) {
            // todo
        }
        // todo
    }
}
