<?php

namespace ForestAdmin\AgentPHP\Agent\Utils;

use ForestAdmin\AgentPHP\Agent\Http\Request;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;

// Todo merge to ID utils ?
class BodyParser
{
    public static function parseSelectionIds(Collection $collection, Request $request): array
    {
        $attributes = $request->get('data')['attributes'];
        $areExcluded = array_key_exists('all_records', $attributes) ? $attributes['all_records'] : false;
        $inputIds = array_key_exists('ids', $attributes) ? $attributes['ids'] : null;
        $ids = Id::unpackIds($collection, $areExcluded ? $attributes['all_records_ids_excluded'] : $inputIds);

        return compact('areExcluded', 'ids');
    }
}
