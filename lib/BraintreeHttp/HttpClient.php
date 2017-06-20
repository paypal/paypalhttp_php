<?php

namespace BraintreeHttp;

use BraintreeHttp;

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
     * @return HttpResponse
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

        # Init curl
        $curl = curl_init();

        # Set the headers
        curl_setopt($curl, CURLOPT_HEADER, $httpRequest->headers);

        # Set verb
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $httpRequest->verb);

        # Set the URL
        $url = $this->environment->baseUrl() . $httpRequest->path;
        curl_setopt($curl, CURLOPT_URL, $url);

        # Set the body
        curl_setopt($curl, CURLOPT_POSTFIELDS, $httpRequest->body);

        # Get a response back instead of printing to page
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        # Perform the curl, get a response back
        $response = curl_exec($curl);
        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $errorCode = curl_errno($curl);
        curl_close($curl);

        /*
        if ($response) {
            echo "Success!";
        }
        else {
            echo "Failed with error code: ";
            echo $error_code;
        }
        echo " Response was: \n";
        echo $response . "\n";
        */

        return new BraintreeHttp\HttpResponse(
            $errorCode === 0 ? $statusCode : $errorCode,
            array(),
            array()
        );
    }

    public function serializeRequest(/* Request */)
    {

    }

    public function deserializeRequest(/* Request */)
    {

    }
}