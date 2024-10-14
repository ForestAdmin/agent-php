<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\FormLayoutElement;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\FormLayoutElement\PageElement as PageElementCustomizer;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;

class PageElement extends LayoutElement
{
    public function __construct(
        protected array $elements,
        protected ?string $nextButtonLabel = null,
        protected ?string $previousButtonLabel = null,
        $if = null,
    ) {
        parent::__construct('Page', $if);

        $this->validateElementsPresence();
        $this->validateNoPageElements();
    }

    private function validateElementsPresence(): void
    {
        if (empty($this->elements)) {
            throw new ForestException("Using 'elements' in a 'Page' configuration is mandatory");
        }
    }

    private function validateNoPageElements(): void
    {
        foreach ($this->elements as $element) {
            if ($element instanceof PageElementCustomizer) {
                throw new ForestException("'Page' component cannot be used within 'elements'");
            }
        }
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
