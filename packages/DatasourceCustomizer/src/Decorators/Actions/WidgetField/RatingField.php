<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\WidgetField;

use DatasourceCustomizer\Decorators\Action\WidgetField\WidgetValidator;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\DynamicField;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\Types\FieldType;

/**
 * @codeCoverageIgnore
*/
class RatingField extends DynamicField
{
    use Widget;

    private int $maxRating;

    public function __construct($options)
    {
        parent::__construct(...$options);
        WidgetValidator::validateArg($options, 'max_rating', ['type' => 'present']);
        WidgetValidator::validateArg($options, 'type', ['type' => 'contains', 'value' => [FieldType::NUMBER]]);
        $this->widget = 'Rating';
        $this->maxRating = $options['max_rating'];
    }

    public function getMaxRating(): int
    {
        return $this->maxRating;
    }
}
