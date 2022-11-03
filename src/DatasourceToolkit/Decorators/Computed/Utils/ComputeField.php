<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Computed\Utils;

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Computed\ComputedCollection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Computed\ComputedDefinition;
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

        foreach ($desiredProjection as $path) {
            self::queueField($collection, $path, $paths, $flatten);
        }

        return Flattener::unFlatten(
            $desiredProjection->map(fn ($path) => $flatten[$paths->search($path)])->toArray(),
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

            foreach ($nestedDependencies as $path) {
                self::queueField($collection, $path, $paths, $flatten);
            }

            $dependencyValues = $nestedDependencies->map(fn ($path) => $flatten[$paths->search($path)])->toArray();
            $paths->push($newPath);

            $flatten[] = self::computeField($computed, $computed->getDependencies(), $dependencyValues);
        }
    }

    public static function computeField(
        ComputedDefinition $computed,
        array              $computedDependencies,
        array              &$flatten
    ): array {
        return self::transformUniqueValues(
            Flattener::unFlatten($flatten, new Projection($computedDependencies)),
            static fn ($uniquePartials) => $computed->getValues($uniquePartials)
        );
    }

    public static function transformUniqueValues(
        array    $inputs,
        \Closure $callback
    ): array {
        $indexes = [];
        $mapping = [];
        $uniqueInputs = [];

        foreach ($inputs as $input) {
            if ($input) {
                $hash = sha1(serialize($input));
                if (! isset($indexes[$hash])) {
                    $indexes[$hash] = count($uniqueInputs);
                    $uniqueInputs[] = $input;
                }
                $mapping[] = $indexes[$hash];
            } else {
                $mapping[] = -1;
            }
        }

        $uniqueOutputs = $callback($uniqueInputs);

        return collect($mapping)->map(fn ($index) => $index !== -1 ? $uniqueOutputs[$index] : null)->toArray();
    }
}