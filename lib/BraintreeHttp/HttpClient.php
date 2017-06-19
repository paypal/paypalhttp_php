<?php

namespace BraintreeHttp;

/**
 * Class HttpClient
 * @package BraintreeHttp
 */
class HttpClient
{
    /**
     * @var Environment
     */
    public $environment;

    /**
     * @var Injector[]
     */
    public $injectors = [];

    /**
     * HttpClient constructor.
     * @param $environment Environment
     */
    function __construct(Environment $environment)
    {
        $this->environment = $environment;
    }

    public function userAgent()
    {
        return "BraintreeHttp-PHP HTTP/1.1";
    }

    public function addInjector(Injector $inj)
    {
        $this->injectors[] = $inj;
    }

    /**
     * @param $httpRequest HttpRequest
     */
    public function execute($httpRequest)
    {
        foreach ($this->injectors as $inj)
        {
            $inj->inject($httpRequest);
        }

        if ($httpRequest->headers["User-Agent"] === NULL)
        {
            $httpRequest->headers["User-Agent"] = $this->userAgent();
        }

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $httpRequest->verb);

        $url = $this->environment->baseUrl() . $httpRequest->path;
        curl_setopt($curl, CURLOPT_URL, $url);

        curl_setopt($curl, CURLOPT_HEADER, $httpRequest->headers);

        $response = curl_exec($curl);
        echo $response;
    }

    public function serializeRequest(/* Request */)
    {

    }

    public function deserializeRequest(/* Request */)
    {

    }
}