<?php

namespace BraintreeHttp;

use BraintreeHttp;
use function GuzzleHttp\headers_from_lines;

/**
 * Class HttpClient makes HTTP requests.
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

        if (!array_key_exists("User-Agent", $httpRequest->headers))
        {
            $httpRequest->headers["User-Agent"] = $this->userAgent();
        }

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_HTTPHEADER, $this->serializeHeaders($httpRequest->headers));
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $httpRequest->verb);
        $url = $this->environment->baseUrl() . $httpRequest->path;
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $this->serializeRequest($httpRequest));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, 1);

        if (strpos($this->environment->baseUrl(), "https://"))
        {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
        }

        if ($caCertPath = $this->getCACertFilePath())
        {
            curl_setopt($curl, CURLOPT_CAINFO, $caCertPath);
        }

        $response = curl_exec($curl);
        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $errorCode = curl_errno($curl);
        $error = curl_error($curl);

        curl_close($curl);

        return $this->parseResponse($response, $statusCode, $errorCode, $error);
    }

    /**
     * @param $headers string
     * @return array
     */
    private function deserializeHeaders($headers)
    {
        $split = explode("\n", $headers);
        array_shift($split);
        $separatedHeaders = [];
        foreach ($split as $header) {
            list($key, $val) = explode(":", $header);
            $separatedHeaders[$key] = trim($val);
        }

        return $separatedHeaders;
    }

    /**
     * @param $headers array[]
     * @return array
     */
    private function serializeHeaders($headers)
    {
        $headerArray = [];
        if ($headers) {
            foreach ($headers as $key => $val) {
               $headerArray[] = $key . ": " . $val;
            }
        }

        return $headerArray;
    }

    /**
     * @param $response object
     * @param $statusCode integer
     * @param $errorCode integer
     * @param $error string
     */
    private function parseResponse($response, $statusCode, $errorCode, $error)
    {
        if ($errorCode > 0)
        {
            throw new IOException($error, $errorCode);
        }

        list($headers, $body) = explode("\r\n\r\n", $response, 2);

        $headers = $this->deserializeHeaders($headers);
        $response = new BraintreeHttp\HttpResponse(
            $errorCode === 0 ? $statusCode : $errorCode,
            $this->deserializeResponse($body, $headers),
            $headers
        );

        if ($response->statusCode >= 200 && $response->statusCode < 300)
        {
            return $response;
        }
        else
        {
            throw new HttpException($response);
        }
    }

    /**
     * Return the filepath to your custom CA Cert if needed.
     * @return string
     */
    protected function getCACertFilePath()
    {
        return null;
    }

    /**
     * @param $request HttpRequest
     * @return string
     */
    public function serializeRequest($request)
    {
        return array_reduce($request->body, function($carry, $item) { return $carry . $item; });
    }

    /**
     * @param $responseBody string
     * @param $headers array
     * @return array | string
     */
    public function deserializeResponse($responseBody, $headers)
    {
        return $responseBody;
    }
}
