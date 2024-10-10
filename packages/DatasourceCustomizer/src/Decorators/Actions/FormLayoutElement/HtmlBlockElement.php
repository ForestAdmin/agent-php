<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\FormLayoutElement;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\BaseFormElement;

/**
 * @codeCoverageIgnore
 */
class HtmlBlockElement extends BaseFormElement
{
    public function __construct(
        protected string $content,
        array $extraArguments = []
    ) {
        parent::__construct('HtmlBlock', $extraArguments);
    }

    public function getContent(): string
    {
        return $this->content;
    }
}
