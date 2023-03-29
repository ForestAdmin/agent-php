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
    public function value(int|float $value, null|int|float $previousValue = null): ValueChart
    {
        return new ValueChart($value, $previousValue);
    }

    public function distribution(array $distribution): PieChart
    {
        return new PieChart(
            collect($distribution)->map(fn ($value, $key) => compact('key', 'value'))->toArray()
        );
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
            ->sort(fn ($dateA, $dateB) =>  $dateA > $dateB ? 1 : -1);

        $first = Carbon::parse($dates->first())->settings(['monthOverflow' => false])->startOf($timeRange);
        $last = Carbon::parse($dates->last())->endOf($timeRange);
        /** @var Carbon $current */
        for ($current = $first; $current <= $last; $current->add($timeRange, 1)) {
            $label = $current->endOf($timeRange)->format($format);
            $dataPoints[] = ['label' => $label, 'values' => [ 'value' => $formatted[$label] ?? 0]];
        }

        return new LineChart($dataPoints);
    }

    /*

    for (const [date, value] of Object.entries(values)) {
      const label = DateTime.fromISO(date).toFormat(format);
      formatted[label] = (formatted[label] ?? 0) + value;
    }

    const dataPoints = [];
    const dates = Object.keys(values).sort((dateA, dateB) => dateA.localeCompare(dateB));
    const first = DateTime.fromISO(dates[0]).startOf(timeRange.toLowerCase() as DateTimeUnit);
    const last = DateTime.fromISO(dates[dates.length - 1]);

    for (let current = first; current <= last; current = current.plus({ [timeRange]: 1 })) {
      const label = current.toFormat(format);
      dataPoints.push({ label, values: { value: formatted[label] ?? 0 } });
    }

    return dataPoints;
    */

    public function percentage(int|float $value): PercentageChart
    {
        return new PercentageChart($value);
    }

    public function objective(int|float $value, null|int|float $objective = null): ObjectiveChart
    {
        return new ObjectiveChart($value, $objective);
    }

    public function leaderboard(array $value): LeaderboardChart
    {
        $data = collect($this->distribution($value)->serialize())
            ->sort(fn ($a, $b) => $b['value'] - $a['value'])
            ->values()
            ->toArray();

        return new LeaderboardChart($data);
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
