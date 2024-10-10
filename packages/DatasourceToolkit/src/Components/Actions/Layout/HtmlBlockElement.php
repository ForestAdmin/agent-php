<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Actions\Layout;

class HtmlBlockElement extends BaseLayoutElement
{
    public function __construct(protected string $content)
    {
        parent::__construct(component: 'HtmlBlock');
    }

    public function getContent(): string
    {
        return $this->content;
    }
}
