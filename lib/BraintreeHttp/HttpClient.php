<?php

namespace BraintreeHttp;

/**
 * Class HttpClient
 * @package BraintreeHttp
 */
class HttpClient
{
    public $environment;
    public $injectors = [];

    function __construct($environment)
    {
        $this->environment = $environment;
    }

    public function user_agent()
    {
        return "BraintreeHttp-PHP HTTP/1.1";
    }

    public function addInjector($inj)
    {
        $this->injectors[] = $inj;
    }

    public function execute(/* Request */)
    {

    }

    public function serializeRequest(/* Request */)
    {

    }

    public function deserializeRequest(/* Request */)
    {

    }
}