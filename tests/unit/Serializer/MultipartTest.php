<?php

namespace Test\Unit;

use BraintreeHttp\HttpRequest;
use BraintreeHttp\Serializer\Multipart;
use PHPUnit\Framework\TestCase;

class MultipartTest extends TestCase {

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage HttpRequest body must be an associative array when Content-Type is:
     */
    public function testMultipart_throwsWhenRequestBodyNotArray() 
    {
        $multipart = new Multipart();

        $request = new HttpRequest("/", "POST");
        $request->body = "";
        $request->headers["Content-Type"] = "multipart/form-data";

        $multipart->serialize($request);
    }

    public function testMultipart_throwsWhenRequestBodyNotAssociativeArray() 
    {
        $multipart = new Multipart();

        $body = [];
        $body[] = "form-param 1";
        $body[] = "form-param 2";

        $request = new HttpRequest("/", "POST");
        $request->body = $body;
        $request->headers["Content-Type"] = "multipart/form-data";
    }
}
