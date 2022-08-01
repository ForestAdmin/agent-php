<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query;

class Page
{
    public function __construct(private int $offset, private int $limit)
    {
    }

    /**
     * @return int
     */
    public function getOffset(): int
    {
        return $this->offset;
    }

    /**
     * @return int
     */
    public function getLimit(): int
    {
        return $this->limit;
    }
}
