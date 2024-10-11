<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\FormLayoutElement;

use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;

/**
 * @codeCoverageIgnore
 */
class PageElement extends LayoutElement
{
    protected array $elements;
    protected ?string $nextButtonLabel;
    protected ?string $previousButtonLabel;

    public function __construct(
        array $elements,
        ?string $nextButtonLabel = null,
        ?string $previousButtonLabel = null,
        array $extraArguments = []
    ) {
        parent::__construct('Page', $extraArguments);

        $this->validateElementsPresence($elements);
        $this->validateNoPageElements($elements);

        $this->elements = $this->instantiateElements($elements);
        $this->nextButtonLabel = $nextButtonLabel;
        $this->previousButtonLabel = $previousButtonLabel;
    }

    private function validateElementsPresence(array $elements): void
    {
        if (empty($elements)) {
            throw new ForestException("Using 'elements' in a 'Page' configuration is mandatory");
        }
    }

    private function validateNoPageElements(array $elements): void
    {
        foreach ($elements as $element) {
            if (isset($element['component']) && $element['component'] === 'Page') {
                throw new ForestException("'Page' component cannot be used within 'elements'");
            }
        }
    }

    private function instantiateElements(array $elements): array
    {
        // TODO
        //return FormFactory::buildElements($elements);
    }
}
