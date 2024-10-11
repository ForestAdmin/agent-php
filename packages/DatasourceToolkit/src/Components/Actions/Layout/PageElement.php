<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Actions\Layout;

class PageElement extends BaseLayoutElement
{
    public function __construct(
        protected array $elements,
        protected ?string $nextButtonLabel = null,
        protected ?string $previousButtonLabel = null,
    ) {
        parent::__construct(component: 'Page');
    }

    public function getElements(): array
    {
        return $this->elements;
    }

    public function getNextButtonLabel(): ?string
    {
        return $this->nextButtonLabel;
    }

    public function getPreviousButtonLabel(): ?string
    {
        return $this->previousButtonLabel;
    }
}
