<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\FormLayoutElement;

class HtmlBlockElement extends LayoutElement
{
    public function __construct(
        protected string $content,
        $if = null
    ) {
        parent::__construct('HtmlBlock', if: $if);
    }

    public function getContent(): string
    {
        return $this->content;
    }
}
