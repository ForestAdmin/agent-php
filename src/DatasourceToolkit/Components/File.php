<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components;

use Illuminate\Support\Collection as IlluminateCollection;

class File
{
    public function __construct(
        protected string  $mimeType,
        protected string  $buffer,
        protected string  $string,
        protected ?string $charset,
    ) {
    }

    /**
     * @return string
     */
    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    /**
     * @return string
     */
    public function getBuffer(): string
    {
        return $this->buffer;
    }

    public function getContent(): IlluminateCollection
    {
        return new IlluminateCollection(
            [
                $this->string,
                $this->charset,
            ]
        );
    }
}
