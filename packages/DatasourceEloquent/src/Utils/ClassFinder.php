<?php

namespace ForestAdmin\AgentPHP\DatasourceEloquent\Utils;

use Composer\ClassMapGenerator\ClassMapGenerator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class ClassFinder
{
    //This value should be the directory that contains composer.json
    //    public const appRoot = __DIR__ . "/../../";
    public const appRoot = "/Users/nicolas/sites/agents/in-app/laravel-apps/laravel-10-blog/";

    public static function getModelsInNamespace(string $namespace): array
    {
        $files = self::fetchFiles(self::getNamespaceDirectory($namespace));

        return array_filter(
            $files,
            static function ($file) {
                if (class_exists($file)) {
                    $class = new \ReflectionClass($file);

                    return $class->isSubclassOf(Model::class) && $class->isInstantiable();
                }
            }
        );
    }

    /**
     * @throws \JsonException
     */
    private static function getDefinedNamespaces(): array
    {
        $composerJsonPath = self::appRoot . 'composer.json';
        $composerConfig = json_decode(file_get_contents($composerJsonPath), false, 512, JSON_THROW_ON_ERROR);

        return (array) $composerConfig->autoload->{'psr-4'};
    }

    /**
     * @throws \JsonException
     */
    private static function getNamespaceDirectory(string $namespace): bool|string
    {
        $composerNamespaces = self::getDefinedNamespaces();

        $namespaceFragments = explode('\\', $namespace);
        $undefinedNamespaceFragments = [];

        while($namespaceFragments) {
            $possibleNamespace = implode('\\', $namespaceFragments) . '\\';
            if(array_key_exists($possibleNamespace, $composerNamespaces)) {
                return realpath(self::appRoot . $composerNamespaces[$possibleNamespace] . implode('/', $undefinedNamespaceFragments));
            }

            array_unshift($undefinedNamespaceFragments, array_pop($namespaceFragments));
        }

        return false;
    }

    private static function fetchFiles(string $directory): array
    {
        $paths = array_diff(scandir($directory), ['.', '..']);
        $allFiles = [];

        foreach ($paths as $path) {
            $fullPath = $directory. DIRECTORY_SEPARATOR .$path;
            $allFiles[] = array_keys(ClassMapGenerator::createMap($fullPath));
        }

        return Arr::flatten($allFiles);
    }
}
