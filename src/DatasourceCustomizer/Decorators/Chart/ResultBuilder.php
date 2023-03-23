<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Chart;

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\LeaderboardChart;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\LineChart;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\ObjectiveChart;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\PercentageChart;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\PieChart;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\ValueChart;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ResultBuilder
{
    public function value(int|float $value, null|int|float $previousValue): ValueChart
    {
        // todo check with Matthieu ValueChart acceept only INT
        return new ValueChart($value, $previousValue);
    }

    public function distribution(array $distribution): PieChart
    {
        // todo test
        return new PieChart($distribution);
    }

    public function timeBased(string $timeRange, array $values): LineChart
    {
        $timeRange = strtolower($timeRange);
        $format = $this->formats($timeRange);
        $formatted = [];
        foreach ($values as $date => $value) {
            $label = date($format, strtotime($date));
            $formatted[$label] = ($formatted[$label] ?? 0) + $value;
        }
        $dataPoints = [];
        $dates = collect($values)->keys()
            ->sort(fn ($dateA, $dateB) => $dateA > $dateB);
        $first = Carbon::parse($dates->first())->startOf($timeRange);
        $last = Carbon::parse($dates->last());
        /** @var Carbon $current */
        for ($current = $first; $current <= $last; $current->add($timeRange, 1)) {
            $label = $current->format($format);
            $dataPoints[] = ['label' => $label, 'values' => [ 'value' => $formatted[$label] ?? 0]];
        }

        return new LineChart($dataPoints);
    }

    public function percentage(int|float $value): PercentageChart
    {
        return new PercentageChart($value);
    }

    public function objective(int|float $value, null|int|float $objective): ObjectiveChart
    {
        return new ObjectiveChart($value, $objective);
    }

    public function leaderboard(array $value): LeaderboardChart
    {
        return new LeaderboardChart($value);
    }

    public function smart($data)
    {
        return $data;
    }

    private function formats(string $timeRange): string
    {
        return match (Str::lower($timeRange)) {
            'day'   => 'd/m/Y',
            'week'  => '\WW-Y',
            'month' => 'M Y',
            'year'  => 'Y',
        };
    }
}
