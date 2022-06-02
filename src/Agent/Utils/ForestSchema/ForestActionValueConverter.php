<?php

namespace ForestAdmin\AgentPHP\Agent\Utils\ForestSchema;

use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ActionField;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\ActionFieldType;

/**
 * This utility class converts form values from our internal format to the format that is
 * used in the frontend for smart action forms.
 */
class ForestActionValueConverter
{
    public static function valueToForest(ActionField $field)
    {
        $value = $field->getValue();
        if ($field->getType() === ActionFieldType::Enum()) {
            return in_array($value, $field->getEnumValues(), true) ? $value : null;
        }

        if ($field->getType() === ActionFieldType::EnumList()) {
            return array_filter(
                (array) $value,
                static fn ($item) => in_array($item, $field->getEnumValues(), true)
            );
        }

        if ($field->getType() === ActionFieldType::Collection()) {
            return implode('|', (array) $value);
        }

        if ($field->getType() === ActionFieldType::File()) {
            //todo
//            return this.makeDataUri(value as File);
        }

        if ($field->getType() === ActionFieldType::FileList()) {
            //todo
//            return (value as File[])?.map(f => this.makeDataUri(f));
        }

        return $value;
    }


//    private static function makeDataUri($file): ?string
//    {
//        if (!$file) return null;
//
//        const [ $mimeType, $buffer, $rest ] = $file;
//        const mediaTypes = Object.entries(rest)
//            .map(([key, value]) => `${key}=${encodeURIComponent(value)}`)
//          .join(';');
//
//        return mediaTypes.length
//            ? `data:${file.mimeType};${mediaTypes};base64,${buffer.toString('base64')}`
//            : `data:${file.mimeType};base64,${buffer.toString('base64')}`;
//    }



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
