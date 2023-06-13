<?php

namespace ForestAdmin\AgentPHP\Agent\Utils\Traits;

use GuzzleHttp\Psr7\Response;

trait FormatGuzzle
{
    /**
     * @param Response $response
     * @return mixed
     * @throws \JsonException
     */
    public function getBody(Response $response)
    {
        return json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR);
    }
}
