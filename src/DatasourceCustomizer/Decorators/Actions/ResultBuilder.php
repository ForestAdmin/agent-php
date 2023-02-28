<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions;

class ResultBuilder
{
    public function success(?string $message = null, array $options = [])
    {
        return [
            'is_action' => true,
            'type'      => 'Success',
            'message'   => $message ?? 'Success',
            'invalided' => $options['invalidated'] ?? [],
            'html'      => $options['html'] ?? [],
        ];
    }

    public function error(?string $message = null, array $options = [])
    {
        return [
            'is_action' => true,
            'type'      => 'Error',
            'message'   => $message ?? 'Error',
            'html'      => $options['html'] ?? [],
        ];
    }

    public function webhook(string $url, $method = 'POST', array $headers = [], array $body = [])
    {
        $type = 'Webhook';
        $is_action = true;

        return compact('is_action', 'type', 'url', 'method', 'headers', 'body');
    }

    public function file($content, string $name = 'file', string $mimeType = 'application/octet-stream')
    {
        return [
            'is_action' => true,
            'type'      => 'File',
            'name'      => $name,
            'mimeType'  => $mimeType,
            'stream'    => $content,
        ];
    }

    public function redirectTo(string $path)
    {
        return [
            'is_action' => true,
            'type'      => 'Redirect',
            'path'      => $path,
        ];
    }
}
