<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\FormLayoutElement;

class SeparatorElement extends LayoutElement
{
    public function __construct(?\Closure $if)
    {
        parent::__construct('Separator', $if);
    }
}
