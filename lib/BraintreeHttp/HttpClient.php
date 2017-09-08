<?php

namespace BraintreeHttp;

use BraintreeHttp;

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

    private $curl;

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

    protected function setCurl(Curl $curl) {
        $this->curl = $curl;
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
        if ($this->curl === null) {
            $this->curl = new Curl();
        }

        foreach ($this->injectors as $inj)
        {
            $inj->inject($httpRequest);
        }

        if (!array_key_exists("User-Agent", $httpRequest->headers))
        {
            $httpRequest->headers["User-Agent"] = $this->userAgent();
        }

        $url = $this->environment->baseUrl() . $httpRequest->path;

        $this->curl->init();
        $this->curl->setOpt(CURLOPT_URL, $url);
        $this->curl->setOpt(CURLOPT_CUSTOMREQUEST, $httpRequest->verb);
        $this->curl->setOpt(CURLOPT_HTTPHEADER, $this->serializeHeaders($httpRequest->headers));
        $this->curl->setOpt(CURLOPT_POSTFIELDS, $this->serializeRequest($httpRequest));
        $this->curl->setOpt(CURLOPT_RETURNTRANSFER, true);
        $this->curl->setOpt(CURLOPT_HEADER, 1);

        if (strpos($this->environment->baseUrl(), "https://") === 0)
        {
            $this->curl->setOpt(CURLOPT_SSL_VERIFYPEER, true);
            $this->curl->setOpt(CURLOPT_SSL_VERIFYHOST, 2);
        }

        if ($caCertPath = $this->getCACertFilePath())
        {
            $this->curl->setOpt(CURLOPT_CAINFO, $caCertPath);
        }

        $response = $this->curl->exec();
        $statusCode = $this->curl->getInfo(CURLINFO_HTTP_CODE);
        $errorCode = $this->curl->errNo();
        $error = $this->curl->error();

        $this->curl->close();

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
            if (!empty($header)) {
                list($key, $val) = explode(":", $header);
                $separatedHeaders[$key] = trim($val);
            }
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
