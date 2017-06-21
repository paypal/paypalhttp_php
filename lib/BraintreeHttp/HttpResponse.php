<?php
namespace BraintreeHttp;

class HttpResponse
{
    /**
     * @var integer
     */
    public $statusCode;

    /**
     * @var object | array | string
     */
    public $result;

    /**
     * @var array
     */
    public $headers;

    /**
     * HttpResponse constructor.
     * @param $statusCode integer
     * @param $body array | string
     * @param $headers array
     */
    public function __construct($statusCode, $body, $headers)
    {
        $this->statusCode = $statusCode;
        $this->headers = $headers;
        $this->result = gettype($body) == "string" ? $body : $this->constructObject($body);
    }

    /**
     * @param $body array
     * @return \stdClass
     */
    private function constructObject($body) {
        $obj = new \stdClass();
        foreach ($body as $key => $val){
            $key = str_replace("-", "_", $key);
            if (is_array($val)) {
                if ($this->isAssoc($val))
                {
                    $obj->$key = $this->constructObject($val);
                }
                else
                {
                    $obj->$key = $val;
                }
            } else {
                $obj->$key = $val;
            }
        }

        return $obj;
    }

    private function isAssoc(array $arr)
    {
        if (array() === $arr) return false;
        return array_keys($arr) !== range(0, count($arr) - 1);
    }
}