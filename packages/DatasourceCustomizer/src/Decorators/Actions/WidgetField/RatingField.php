<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\WidgetField;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\DynamicField;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\Types\FieldType;
use ForestAdminDatasourceCustomizer\Decorators\Action\WidgetField\WidgetField;

class RatingField extends DynamicField
{
    use Widget;

    private int $maxRating;

    public function __construct($options)
    {
        parent::__construct($options['type'], $options['label']);
        WidgetField::validateArg($options, 'max_rating', ['type' => 'present']);
        WidgetField::validateArg($options, 'type', ['type' => 'contains', 'value' => [FieldType::NUMBER]]);
        $this->widget = 'Rating';
        $this->maxRating = $options['max_rating'];
    }

    public function getMaxRating(): int
    {
        return $this->maxRating;
    }
}
