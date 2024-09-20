<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\WidgetField;

trait Widget
{
    private string $widget;

    public function getWidget(): string
    {
        return $this->widget;
    }

    public function setWidget(string $widget): void
    {
        $this->widget = $widget;
    }
}
