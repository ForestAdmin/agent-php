<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\Context;

class ActionContextSingle extends ActionContext
{
    public function getRecord(array $fields): array
    {
        $records = $this->getRecords($fields);

        return $records[0] ?? [];
    }

    public function getRecordId()
    {
        $compositeId = $this->getCompositeRecordId();

        return $compositeId[0] ?? null;
    }

    public function getCompositeRecordId(): array
    {
        $ids = $this->getCompositeRecordIds();

        return $ids[0] ?? [];
    }
}
