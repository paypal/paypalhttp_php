<?php

namespace Test\Unit;

use BraintreeHttp\Encoder;
use BraintreeHttp\HttpRequest;
use BraintreeHttp\Serializer;
use PHPUnit\Framework\TestCase;

class EncoderTest extends TestCase
{
    /**
     * @expectedException \Exception
     * @expectedExceptionMessage HttpRequest does not have Content-Type header set
     */
    public function testEncode_throwsExceptionIfContentTypeNotPresent()
    {
        $encoder = new Encoder();
        $httpRequest = new HttpRequest("/path", "post");
        $httpRequest->body = "some string";

        $encoder->encode($httpRequest);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Unable to serialize request with Content-Type: non-existent/type. Supported encodings are
     */
    public function testEncode_throwsExceptionIfNoSerializerForGivenContentType()
    {
        $encoder = new Encoder();
        $httpRequest = new HttpRequest("/path", "post");
        $httpRequest->headers['Content-Type'] = "non-existent/type";
        $httpRequest->body = "some string";

        $encoder->encode($httpRequest);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Body must be either string or array
     */
    public function testEncode_throwsExceptionForNonStringOrArrayBody()
    {
        $encoder = new Encoder();
        $httpRequest = new HttpRequest("/path", "post");
        $httpRequest->headers['Content-Type'] = "application/json";
        $httpRequest->body = new \stdClass();

        $encoder->encode($httpRequest);
    }

    public function testEncode_serializesWithCorrectSerializer()
    {
        $encoder = new Encoder();
        $httpRequest = new HttpRequest("/path", "post");
        $httpRequest->headers['Content-Type'] = "application/json";
        $httpRequest->body = [
            "key_one" => "value_one",
            "key_two" => [
                "one",
                "two"
            ]
        ];

        $result = $encoder->encode($httpRequest);

        $this->assertEquals('{"key_one":"value_one","key_two":["one","two"]}', $result);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage HTTP response does not have Content-Type header set
     */
    public function testDecode_throwsWhenContentTypeNotPresent()
    {
        $encoder = new Encoder();
        $headers = [];

        $encoder->decode('data', $headers);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Unable to deserialize response with Content-Type: application/unstructured. Supported encodings are:
     */
    public function testDecode_throwsWhenNoSerializerAvailableForContentType()
    {
        $encoder = new Encoder();
        $headers = [
            "Content-Type" => "application/unstructured"
        ];

        $encoder->decode('data', $headers);
    }

    public function testDecode_deserializesResponseWithCorrectSerializer()
    {
        $encoder = new Encoder();
        $responseBody = '{"key_one":"value_one","key_two":["one","two"]}';
        $headers = [
            "Content-Type" => "application/json"
        ];

        $result = $encoder->decode($responseBody, $headers);

        $this->assertEquals("value_one", $result->key_one);
        $this->assertEquals(["one", "two"], $result->key_two);
    }

    public function testDecode_deserializesResponseWithContentEncoding()
    {
        $encoder = new Encoder();
        $responseBody = '{"key_one":"value_one","key_two":["one","two"]}';
        $headers = [
            "Content-Type" => "application/json; charset=utf-8"
        ];

        $result = $encoder->decode($responseBody, $headers);

        $this->assertEquals("value_one", $result->key_one);
        $this->assertEquals(["one", "two"], $result->key_two);
    }
}
