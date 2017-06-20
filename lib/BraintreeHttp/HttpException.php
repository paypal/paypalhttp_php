<?php
namespace BraintreeHttp;


use Throwable;

class HttpException extends IOException
{
    /**
     * @var HttpResponse
     */
    public $response;

    /**
     * HttpException constructor.
     * @param string $response
     */
    public function __construct($response)
    {
        parent::__construct();
        $this->response = $response;
    }
}