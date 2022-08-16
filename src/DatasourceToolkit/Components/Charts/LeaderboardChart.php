<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts;

use ForestAdmin\AgentPHP\DatasourceToolkit\Validations\ChartValidator;

class LeaderboardChart extends Chart
{
    public function __construct(protected array $data)
    {
    }

    public function serialize(): array
    {
        foreach ($this->data as $item) {
            ChartValidator::validate((! array_key_exists('key', $item) || ! array_key_exists('value', $item)), $item, "'key', 'value'");
        }

        $result = collect($this->data)->each(
            fn ($item) => ['key' => $item['key'], 'value' => $item['value']]
        );

        return $result->toArray();
    }
}
