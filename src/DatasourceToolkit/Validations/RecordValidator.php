<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Validations;

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\CollectionContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;

class RecordValidator
{
    public static function validate(CollectionContract $collection, array $recordData): void
    {
        if (empty($recordData)) {
            throw new ForestException('The record data is empty');
        }

        foreach ($recordData as $key) {
            $field = $collection->getFields()[$key];

            if (! $field) {
                throw new ForestException("Unknown field $key");
            } elseif ($field->getType() === 'Column') {
                FieldValidator::validate($collection, $key, $recordData[$key]);
            } elseif ($field->getType() === 'ManyToOne' || $field->getType() === 'OneToOne') {
                $subRecord = $recordData[$key];
                $association = $collection->getDataSource()->getCollection($field->getForeignCollection());
                RecordValidator::validate($association, $subRecord);
            } else {
                throw new ForestException('Unexpected schema type ' . $field->getType() . ' while traversing record');
            }
        }
    }
}
