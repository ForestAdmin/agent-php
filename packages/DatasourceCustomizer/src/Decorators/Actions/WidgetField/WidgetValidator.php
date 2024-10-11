<?php

namespace ForestAdminDatasourceCustomizer\Decorators\Action\WidgetField;

use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;

/**
 * @codeCoverageIgnore
 */
class WidgetValidator
{
    public static function validateArg($options, $attribute, $rule)
    {
        switch ($rule[$attribute]) {
            case 'contains':
                if (! in_array($options[$attribute], $rule['value'], true)) {
                    throw new ForestException("'$attribute' must have a value included in [" . implode(',', $rule['value']) . "]");
                }

                break;

            case 'present':
                if (! array_key_exists($attribute, $options)) {
                    throw new ForestException("key '$attribute' must be defined");
                }

                break;
        }
    }
}
