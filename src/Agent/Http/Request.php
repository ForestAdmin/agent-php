<?php

namespace ForestAdmin\AgentPHP\Agent\Http;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

class Request extends SymfonyRequest
{
    /**
     * Creates a new request with values from PHP's super globals.
     *
     * @throws \JsonException
     */
    public static function createFromGlobals(): static
    {
        $input = file_get_contents('php://input');
        $json = $input ? json_decode($input, true, 512, JSON_THROW_ON_ERROR) : [];

        // fill $_POST with the body json content
        $_POST = array_merge($_POST, $json);

        return parent::createFromGlobals();
    }

    /**
     * Get all of the input and files for the request.
     *
     * @param  array|mixed|null  $keys
     * @return array
     */
    public function all($keys = null)
    {
        $input = $this->input();

        if (! $keys) {
            return $input;
        }

        $results = [];

        foreach (is_array($keys) ? $keys : func_get_args() as $key) {
            Arr::set($results, $key, Arr::get($input, $key));
        }

        return $results;
    }

    /**
     * Retrieve an input item from the request.
     *
     * @param  string|null  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function input($key = null, $default = null)
    {
        return data_get(
            $this->request->all() + $this->query->all(),
            $key,
            $default
        );
    }
}
