<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions;

class ResultBuilder
{
    public function success(?string $message = null, array $options = [])
    {
        return [
            'type'      => 'Success',
            'message'   => $message ?? 'Success',
            'invalided' => $options['invalidated'] ?? [],
            'html'      => $options['html'] ?? [],
        ];
    }

    public function error(?string $message = null, array $options = [])
    {
        return [
            'type'    => 'Error',
            'message' => $message ?? 'Error',
            'html'    => $options['html'] ?? [],
        ];
    }

    public function webhook(string $url, $method = 'POST', array $headers = [], array $body = [])
    {
        $type = 'Webhook';

        return compact('type', 'url', 'method', 'headers', 'body');
    }

    public function file(string $name = 'file', string $mimeType = 'application/octet-stream')
    {
        return [
            'type'     => 'File',
            'name'     => $name,
            'mimeType' => $mimeType,
            'stream'   => null, //todo
        ];
    }

    public function redirectTo(string $path)
    {
        return [
            'type' => 'Redirect',
            'path' => $path,
        ];
    }
}
