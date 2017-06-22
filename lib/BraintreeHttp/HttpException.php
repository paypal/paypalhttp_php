<?php
namespace BraintreeHttp;

class HttpException extends IOException
{
    /**
     * @var HttpResponse
     */
    public $response;

    /**
     * @param string $response
     */
    public function __construct($response)
    {
        parent::__construct();
        $this->response = $response;
    }
}
