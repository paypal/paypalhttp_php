<?php
namespace Test\Unit;

use BraintreeHttp\HttpResponse;
use PHPUnit\Framework\TestCase;

class HttpResponseTest extends TestCase
{
    public function testHttpResponse_constructsWithAString()
    {
        $response = new HttpResponse(200, '{"myJSON"=> "isTheBestJSON"}', ["Content-Type" => "application/json"]);

        $this->assertEquals(200, $response->statusCode);
        $this->assertEquals('{"myJSON"=> "isTheBestJSON"}', $response->result);
        $this->assertEquals(["Content-Type" => "application/json"], $response->headers);
    }

    public function testHttpResponse_constructsAnObjectFromAnArray()
    {
        $body = [
            "int" => 100,
            "str" => "value",
            "nested" => [
                "key-one" => "value-one",
                "key-two" => 123.456,
                "key-three" => ["abc", "def", "ghi"]
            ]
        ];

        $response = new HttpResponse(200, $body, ["Content-Type" => "application/xml"]);

        $this->assertEquals(200, $response->statusCode);
        $this->assertEquals(100, $response->result->int);
        $this->assertEquals("value", $response->result->str);
        $this->assertEquals("value-one", $response->result->nested->key_one);
        $this->assertEquals(123.456, $response->result->nested->key_two);
        $this->assertEquals("abc", $response->result->nested->key_three[0]);
        $this->assertEquals("def", $response->result->nested->key_three[1]);
        $this->assertEquals("ghi", $response->result->nested->key_three[2]);
        $this->assertEquals(["Content-Type" => "application/xml"], $response->headers);
    }
}
