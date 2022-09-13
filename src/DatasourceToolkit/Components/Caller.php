<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class Caller
{
    public function __construct(
        protected int $id,
        protected string $email,
        protected string $firstName,
        protected string $lastName,
        protected string $team,
        protected int $renderingId,
        protected array $tags,
        protected string $timezone,
        protected ?string $role = null,
    ) {
    }

    public static function makeFromRequestData(object $requestObjectData, $timezone): self
    {
        // cast object to array recursively
        $toArray = function ($x) use (&$toArray) {
            return is_scalar($x)
                ? $x
                : array_map($toArray, (array) $x);
        };
        $data = $toArray($requestObjectData);

        $data['timezone'] = $timezone;
        unset($data['exp']);

        $attributes = [];
        foreach ($data as $key => $value) {
            $attributes[Str::camel($key)] = $value;
        }

        return (new static(...$attributes));
    }

    /**
     * @return string
     */
    public function getTimezone(): string
    {
        return $this->timezone;
    }

    public function getRenderingId(): int
    {
        return $this->renderingId;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param string $key
     * @return ?string
     */
    public function getTag(string$key): ?string
    {
        return $this->tags[$key] ?: null;
    }

    public function getValue(string $key)
    {
        return property_exists($this, $key) ? $this->$key : null;
    }
}
