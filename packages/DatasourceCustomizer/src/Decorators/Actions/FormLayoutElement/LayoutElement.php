<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\FormLayoutElement;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\BaseFormElement;

class LayoutElement extends BaseFormElement
{
    public function __construct(
        protected string $component,
<<<<<<< HEAD
        protected ?\Closure $if = null,
=======
        protected ?string $ifCondition = null,
>>>>>>> 029dd7f (chore: add widget fields & form elements (#123))
        array $extraArguments = []
    ) {
        parent::__construct('Layout', $extraArguments);
    }

    public function getComponent(): string
    {
        return $this->component;
    }

<<<<<<< HEAD
    public function getIf(): ?\Closure
    {
        return $this->if;
    }

    public function setIf(?string $if): void
    {
        $this->if = $if;
=======
    public function getIfCondition(): ?string
    {
        return $this->ifCondition;
>>>>>>> 029dd7f (chore: add widget fields & form elements (#123))
    }
}
