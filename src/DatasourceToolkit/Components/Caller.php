<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components;

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
        $data = (array) $requestObjectData;
        $data['timezone'] = $timezone;
        unset($data['exp']);

        $attributes = [];
        foreach ($data as $key => $value) {
            $attributes[Str::camel($key)] = $value;
        }

        return (new static(...$attributes));
    }
}
