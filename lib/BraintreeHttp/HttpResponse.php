<?php
namespace BraintreeHttp;

class HttpResponse
{
    /**
     * @var integer
     */
    public $code;

    /**
     * @var array
     */
    public $responseBody;

    /**
     * @var boolean
     */
    public $successful;

    /**
     * HttpResponse constructor.
     * @param $successful boolean
     * @param $code integer
     * @param $responseBody array
     */
    public function __construct($successful, $code, $responseBody)
    {
        $this->successful = $successful;
        $this->code = $code;
        $this->responseBody = $responseBody;
    }
}