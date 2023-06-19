<?php

namespace ForestAdmin\AgentPHP\Agent\Utils\ForestSchema;

use ForestAdmin\AgentPHP\Agent\Utils\Id;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\DynamicField;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\Types\FieldType;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\ActionField;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\DatasourceContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\File;
use Illuminate\Support\Str;

/**
 * This utility class converts form values from our internal format to the format that is
 * used in the frontend for smart action forms.
 */
class ForestActionValueConverter
{
    public static function valueToForest(ActionField|DynamicField $field)
    {
        $value = $field->getValue();

        if ($field->getType() === FieldType::COLLECTION) {
            return implode('|', (array) $value);
        }

        if (is_a($value, File::class) && $field->getType() === FieldType::FILE) {
            return self::makeDataUri($value);
        }

        if (is_a($value, File::class) && $field->getType() === FieldType::FILE_LIST) {
            return array_map(
                static fn ($file) => self::makeDataUri($file)
            );
        }

        return $value;
    }

    public static function makeFormDataFromFields(DatasourceContract $datasource, array $fields): array
    {
        $data = [];
        foreach ($fields as $field) {
            // Skip fields from the default form
            if (! collect(GeneratorAction::$defaultFields)->map(fn ($f) => $f['field'])->contains($field['field'])) {
                if ($field['reference'] && $field['value']) {
                    $collectionName = Str::before($field['reference'], '.');
                    $collection = $datasource->getCollection($collectionName);
                    $data[$field['field']] = Id::unpackId($collection, $field['value']);
                } elseif ($field['type'] === FieldType::FILE) {
                    $data[$field['field']] = self::parseDataUri($field['value']);
                } elseif (is_array($field['type']) && $field['type'][0] === FieldType::FILE) {
                    $data[$field['field']] = collect($field['value'])->map(fn ($v) => $this->parseDataUri($v));
                } else {
                    $data[$field['field']] = $field['value'];
                }
            }
        }

        return $data;
    }

    private static function makeDataUri(File $file): ?string
    {
        $mimeType = $file->getMimeType();
        $buffer = base64_encode($file->getBuffer());
        unset($file[0], $file[1]);

        $mediaTypes = $file->getContent()
                ->map(fn ($value, $key) => $key . '=' . urlencode($value))
                ->join(';');

        return $mediaTypes !== ''
            ? "data:$mimeType;$mediaTypes;base64,$buffer"
            : "data:$mimeType;base64,$buffer";
    }

    private static function parseDataUri(?string $dataUri)
    {
        if (! $dataUri) {
            return null;
        }

        [$header, $data] = explode(',', substr($dataUri, 1));
        $mediaTypes = explode(';', $header);
        $mimeType = array_shift($mediaTypes);
        $result = ['mimeType' => $mimeType, 'buffer' => base64_decode($data)];

        foreach ($mediaTypes as $mediaType) {
            $index = strpos($mediaType, '=');
            if (! $index) {
                $result[substr($mediaType, 0, $index)] = urldecode(substr($mediaType, $index + 1));
            }
        }

        return $result;
    }

    private static function isDataUri($value): bool
    {
        return is_string($value) && str_starts_with($value, 'data:');
    }
}
