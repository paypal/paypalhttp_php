<?php

namespace BraintreeHttp;

/**
 * Class HttpClient
 * @package BraintreeHttp
 *
 * Client used to make HTTP requests.
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
     * @var Curl
     */
    private $curl;

    /**
     * @var Encoder
     */
    private $encoder;

    /**
     * HttpClient constructor. Pass the environment you wish to make calls to.
     *
     * @param $environment Environment
     * @see Environment
     */
    function __construct(Environment $environment)
    {
        $this->environment = $environment;
        $this->encoder = new Encoder();
    }

    /**
     * Injectors are blocks that can be used for executing arbitrary pre-flight logic, such as modifying a request or logging data.
     * Executed in first-in first-out order.
     *
     * @param Injector $inj
     */
    public function addInjector(Injector $inj)
    {
        $this->injectors[] = $inj;
    }

    /**
     * The method that takes an HTTP request, serializes the request, makes a call to given environment, and deserialize response
     *
     * @param $httpRequest HttpRequest
     * @return HttpResponse
     */
    public function execute(HttpRequest $httpRequest)
    {
        if ($this->curl === null) {
            $this->curl = new Curl();
        }

        foreach ($this->injectors as $inj) {
            $inj->inject($httpRequest);
        }

        if (!array_key_exists("User-Agent", $httpRequest->headers)) {
            $httpRequest->headers["User-Agent"] = $this->userAgent();
        }

        $url = $this->environment->baseUrl() . $httpRequest->path;

        $this->curl->init();
        $this->curl->setOpt(CURLOPT_URL, $url);
        $this->curl->setOpt(CURLOPT_CUSTOMREQUEST, $httpRequest->verb);
        $this->curl->setOpt(CURLOPT_HTTPHEADER, $this->serializeHeaders($httpRequest->headers));
        $this->curl->setOpt(CURLOPT_RETURNTRANSFER, true);
        $this->curl->setOpt(CURLOPT_HEADER, 1);

        if (!is_null($httpRequest->body)) {
            $this->curl->setOpt(CURLOPT_POSTFIELDS, $this->serializeRequest($httpRequest));
        }

        if (strpos($this->environment->baseUrl(), "https://") === 0) {
            $this->curl->setOpt(CURLOPT_SSL_VERIFYPEER, true);
            $this->curl->setOpt(CURLOPT_SSL_VERIFYHOST, 2);
        }

        if ($caCertPath = $this->getCACertFilePath()) {
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
     * Returns default user-agent
     *
     * @return string
     */
    public function userAgent()
    {
        return "BraintreeHttp-PHP HTTP/1.1";
    }

    /**
     * Serialize request to be sent to server
     *
     * @param $request HttpRequest
     * @return string
     */
    public function serializeRequest(HttpRequest $request)
    {
        return $this->encoder->encode($request);
    }

    /**
     * De-serializes response received from server to expected output.
     *
     * @param $responseBody string
     * @param $headers array
     * @return mixed de-serialized response
     */
    public function deserializeResponse($responseBody, $headers)
    {
        return $this->encoder->decode($responseBody, $headers);
    }

    /**
     * Return the filepath to your custom CA Cert if needed.
     * @return string
     */
    protected function getCACertFilePath()
    {
        return null;
    }

    protected function setCurl(Curl $curl)
    {
        $this->curl = $curl;
    }

    protected function setEncoder(Encoder $encoder)
    {
        $this->encoder = $encoder;
    }

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

    private function parseResponse($response, $statusCode, $errorCode, $error)
    {
        if ($errorCode > 0) {
            throw new IOException($error, $errorCode);
        }

        list($headers, $body) = explode("\r\n\r\n", $response, 2);

        $headers = $this->deserializeHeaders($headers);
        $responseBody = NULL;
        if (!ctype_space($body)) {
            $responseBody = $this->deserializeResponse($body, $headers);
        }

        $response = new HttpResponse(
            $errorCode === 0 ? $statusCode : $errorCode,
            $responseBody,
            $headers
        );

        if ($response->statusCode >= 200 && $response->statusCode < 300) {
            return $response;
        } else {
            throw new HttpException($response);
        }
    }

    private function deserializeHeaders($headers)
    {
        if (strlen($headers) > 0) {
            $split = explode("\r\n", $headers);
            array_shift($split);
            $separatedHeaders = [];
            foreach ($split as $header) {
                if (!empty($header)) {
                    list($key, $val) = explode(":", $header);
                    $separatedHeaders[$key] = trim($val);
                }
            }

            return $separatedHeaders;
        } else {
            return [];
        }
    }
}
