<?php

namespace ForestAdmin\AgentPHP\Agent\Utils\ForestSchema;

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\ActionField;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Concerns\ActionFieldType;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\File;

/**
 * This utility class converts form values from our internal format to the format that is
 * used in the frontend for smart action forms.
 */
class ForestActionValueConverter
{
    public static function valueToForest(ActionField $field)
    {
        $value = $field->getValue();

        if ($field->getType() === ActionFieldType::Collection()) {
            return implode('|', (array) $value);
        }

        if (is_a($value, File::class) && $field->getType() === ActionFieldType::File()) {
            return self::makeDataUri($value);
        }

        if (is_a($value, File::class) && $field->getType() === ActionFieldType::FileList()) {
            return array_map(
                static fn ($file) => self::makeDataUri($file)
            );
        }

        return $value;
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



//    private static function parseDataUri(string $dataUri): File
//    {
    ////        if (!dataUri) return null;
    ////
    ////        // Poor man's data uri parser (spec compliants one don't get the filename).
    ////        // Hopefully this does not break.
    ////        const [header, data] = dataUri.substring(5).split(',');
    ////        const [mimeType, ...mediaTypes] = header.split(';');
    ////        const result = { mimeType, buffer: Buffer.from(data, 'base64') };
    ////
    ////        for (const mediaType of mediaTypes) {
    ////        const index = mediaType.indexOf('=');
    ////        if (index !== -1)
    ////            result[mediaType.substring(0, index)] = decodeURIComponent(mediaType.substring(index + 1));
    ////        }
    ////
    ////        return result as File;
//    }

    private static function isDataUri($value): bool
    {
        return is_string($value) && str_starts_with($value, 'data:');
    }
}
