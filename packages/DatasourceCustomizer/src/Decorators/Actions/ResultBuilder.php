<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions;

class ResultBuilder
{
    protected array $headers = [];

    public function setHeader(string $key, string $value): ResultBuilder
    {
        $this->headers[$key] = $value;

        return $this;
    }

    public function success(?string $message = null, array $options = [])
    {
        return [
            'headers'   => $this->headers,
            'is_action' => true,
            'type'      => 'Success',
            'success'   => $message ?? 'Success',
            'refresh'   => ['relationships' => $options['invalidated'] ?? []],
            'html'      => $options['html'] ?? null,
        ];
    }

    public function error(?string $message = null, array $options = [])
    {
        return [
            'headers'     => $this->headers,
            'status'      => 400,
            'is_action'   => true,
            'type'        => 'Error',
            'error'       => $message ?? 'Error',
            'html'        => $options['html'] ?? null,
        ];
    }

    public function webhook(string $url, $method = 'POST', array $headers = [], array $body = [])
    {
        return [
            'headers'   => $this->headers,
            'type'      => 'Webhook',
            'is_action' => true,
            'webhook'   => compact('url', 'method', 'headers', 'body'),
        ];
    }

    public function file($content, string $name = 'file', string $mimeType = 'application/octet-stream')
    {
        return [
            'headers'   => $this->headers,
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
            'headers'    => $this->headers,
            'is_action'  => true,
            'type'       => 'Redirect',
            'redirectTo' => $path,
        ];
    }
}
