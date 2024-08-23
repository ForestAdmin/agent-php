<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Computed\Utils;

class Undefined
{
    public function __call($name, $arguments)
    {
        return $this;
    }

    public function __get($name)
    {
        return $this;
    }

    public function __set($name, $value)
    {
        return $this;
    }

    public function __isset($name)
    {
        return false;
    }

    public function __unset($name)
    {
        return;
    }
}
