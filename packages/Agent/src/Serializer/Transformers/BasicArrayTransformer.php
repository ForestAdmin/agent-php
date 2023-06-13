<?php

namespace ForestAdmin\AgentPHP\Agent\Serializer\Transformers;

use League\Fractal\TransformerAbstract;

class BasicArrayTransformer extends TransformerAbstract
{
    /**
     * @param array $data
     * @return array
     */
    public function transform(array $data)
    {
        return $data;
    }
}
