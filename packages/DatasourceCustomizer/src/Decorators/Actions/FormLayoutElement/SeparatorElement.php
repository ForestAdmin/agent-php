<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\FormLayoutElement;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\BaseFormElement;

class SeparatorElement extends BaseFormElement
{
    public function __construct(array $extraArguments = [])
    {
        parent::__construct('Separator', $extraArguments);
    }
}
