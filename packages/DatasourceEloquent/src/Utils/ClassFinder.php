<?php

namespace ForestAdmin\AgentPHP\DatasourceEloquent\Utils;

use Composer\ClassMapGenerator\ClassMapGenerator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class ClassFinder
{
    public function __construct(protected string $appRoot)
    {
    }

    public function getModelsInNamespace(string $namespace): array
    {
        $files = $this->fetchFiles($this->getNamespaceDirectory($namespace));

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
    private function getDefinedNamespaces(): array
    {
        $composerJsonPath = $this->appRoot . '/composer.json';
        $composerConfig = json_decode(file_get_contents($composerJsonPath), false, 512, JSON_THROW_ON_ERROR);

        return (array) $composerConfig->autoload->{'psr-4'};
    }

    /**
     * @throws \JsonException
     */
    private function getNamespaceDirectory(string $namespace): bool|string
    {
        $composerNamespaces = $this->getDefinedNamespaces();

        $namespaceFragments = explode('\\', $namespace);
        $undefinedNamespaceFragments = [];

        while($namespaceFragments) {
            $possibleNamespace = implode('\\', $namespaceFragments) . '\\';
            if(array_key_exists($possibleNamespace, $composerNamespaces)) {
                return realpath($this->appRoot . '/' . $composerNamespaces[$possibleNamespace] . implode('/', $undefinedNamespaceFragments));
            }

            array_unshift($undefinedNamespaceFragments, array_pop($namespaceFragments));
        }

        return false;
    }

    private function fetchFiles(string $directory): array
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
