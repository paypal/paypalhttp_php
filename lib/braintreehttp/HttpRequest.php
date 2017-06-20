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

    /**
     * @param $echo bool
     * @return string
     */
    function _print($echo=true)
    {
        $str = $this->path . "\n";
        $str = $str . $this->verb . "\n";

        if ($this->headers) {
            foreach ($this->headers as $key => $val) {
                $str = $str . $key . ": " . $val . "\n";
            }
        }

        if ($this->body) {
            foreach ($this->body as $key => $val) {
                $str = $str . $key . ": " . $val . "\n";
            }
        }

        if ($echo) {
            echo $str;
        }

        return $str;
    }
}
