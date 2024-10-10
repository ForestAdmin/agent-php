<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\FormLayoutElement;

class HtmlBlockElement extends LayoutElement
{
    public function __construct(
        protected string $content,
    ) {
        parent::__construct('HtmlBlock');
    }

    public function getContent(): string
    {
        return $this->content;
    }
}
