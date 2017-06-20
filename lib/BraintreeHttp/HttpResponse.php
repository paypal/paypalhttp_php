<?php
namespace BraintreeHttp;

class HttpResponse
{
    /**
     * @var integer
     */
    public $statusCode;

    /**
     * @var string
     */
    public $body;

    /**
     * @var array
     */
    public $headers;

    /**
     * HttpResponse constructor.
     * @param $statusCode integer
     * @param $body array
     * @param $headers array
     */
    public function __construct($statusCode, $body, $headers)
    {
        $this->statusCode = $statusCode;
        $this->headers = $headers;
        // $this->result = $this->constructObject($body);
        $this->body = $body;
    }

    /**
     * @param $body object
     * @return \stdClass
     */
    private function constructObject($body) {
        $obj = new \stdClass();
        foreach ($body as $key => $val){
            if (is_array($val)) {
                $obj->$key = $this->constructObject($val);
            } else {
                $obj->$key = $val;
            }
        }

        return $obj;
    }
}