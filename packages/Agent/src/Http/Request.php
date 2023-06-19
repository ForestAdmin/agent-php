<?php

namespace ForestAdmin\AgentPHP\Agent\Http;

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

    public function set(string $key, $value): void
    {
        $this->request->set($key, $value);
    }

    /**
     * Retrieve a header from the request.
     *
     * @param  string|null  $key
     * @param  string|array|null  $default
     * @return string|array|null
     */
    public function header($key = null, $default = null)
    {
        return $this->retrieveItem('headers', $key, $default);
    }

    /**
     * Get the bearer token from the request headers.
     *
     * @return string|null
     */
    public function bearerToken()
    {
        $header = $this->header('Authorization', '');

        $position = strrpos($header, 'Bearer ');

        if ($position !== false) {
            $header = substr($header, $position + 7);

            return strpos($header, ',') !== false ? strstr($header, ',', true) : $header;
        }
    }

    /**
     * Get all of the input and files for the request.
     *
     * @param  array|mixed|null  $keys
     * @return array
     */
    public function all()
    {
        return $this->input();
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

    public function has($key)
    {
        $keys = is_array($key) ? $key : func_get_args();

        $input = $this->all();

        foreach ($keys as $value) {
            if (! Arr::has($input, $value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Retrieve a parameter item from a given source.
     *
     * @param  string  $source
     * @param  string  $key
     * @param  string|array|null  $default
     * @return string|array|null
     */
    protected function retrieveItem($source, $key, $default)
    {
        if (is_null($key)) {
            return $this->$source->all();
        }

        return $this->$source->get($key, $default);
    }
}
