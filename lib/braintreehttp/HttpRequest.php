<?php

namespace BraintreeHttp;

class HttpRequest
{
    /**
     * @var string
     */
    public $path;

    /**
     * @var array
     */
    public $body;

    /**
     * @var string
     */
    public $verb;

    /**
     * @var array
     */
    public $headers;

    function __construct($path, $verb)
    {
        $this->path = $path;
        $this->verb = $verb;
    }
}
