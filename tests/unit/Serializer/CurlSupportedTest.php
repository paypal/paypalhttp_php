<?php

namespace Test\Unit;

use BraintreeHttp\HttpRequest;
use BraintreeHttp\Serializer\CurlSupported;
use PHPUnit\Framework\TestCase;

class CurlSupportedTest extends TestCase {

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage HttpRequest body must be an associative array when Content-Type is:
     */
    public function testCurlSupportedThrowsWhenRequestBodyNotArray()
    {
        $multipart = new CurlSupported();

        $request = new HttpRequest("/", "POST");
        $request->body = "";
        $request->headers["Content-Type"] = "multipart/form-data";

        $multipart->serialize($request);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage HttpRequest body must be an associative array when Content-Type is:
     */
    public function testCurlSupportedThrowsWhenRequestBodyNotAssociativeArray()
    {
        $multipart = new CurlSupported();

        $body = [];
        $body[] = "form-param 1";
        $body[] = "form-param 2";

        $request = new HttpRequest("/", "POST");
        $request->body = $body;
        $request->headers["Content-Type"] = "multipart/form-data";

        $multipart->serialize($request);
    }
}
