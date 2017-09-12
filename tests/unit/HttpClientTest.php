<?php

namespace Test\Unit;

use BraintreeHttp\Curl;
use BraintreeHttp\Environment;
use BraintreeHttp\HttpClient;
use BraintreeHttp\HttpException;
use BraintreeHttp\HttpRequest;
use BraintreeHttp\Injector;
use PHPUnit\Framework\TestCase;

class HttpClientTest extends TestCase
{

    public function testAddInjector_addsInjectorToInjectorList()
    {
        $environment = new DevelopmentEnvironment("http://localhost");
        $client = new MockHttpClient($environment);

        $inj = new BasicInjector();
        $client->addInjector($inj);

        $this->assertContains($inj, $client->injectors);
    }

    public function testAddsMultipleInjectors_addsMultipleInjectorsToInjectorList()
    {
        $environment = new DevelopmentEnvironment("http://localhost");
        $client = new MockHttpClient($environment);

        $inj1 = new BasicInjector();
        $client->addInjector($inj1);

        $inj2 = new BasicInjector();
        $client->addInjector($inj2);

        $this->assertArraySubset([$inj1, $inj2], $client->injectors);
    }

    public function testExecute_usesInjectorsToModifyRequest()
    {
        $environment = new DevelopmentEnvironment("http://localhost");
        $mock = \Mockery::mock(new MockCurl(200))->makePartial();
        $client = new MockHttpClient($environment, $mock);

        $injector = new BasicInjector();
        $client->addInjector($injector);

        $req = new HttpRequest("/path", "GET");
        $client->execute($req);

        $this->assertEquals("/some-other-path", $req->path);
    }

    public function testExecute_formsRequestProperly()
    {
        $environment = new DevelopmentEnvironment("http://localhost");
        $mock = \Mockery::mock(new MockCurl(200))->makePartial();
        $client = new MockHttpClient($environment, $mock);

        $req = new HttpRequest("/path", "POST");
        $req->headers["Content-Type"] = "application/json";
        $req->body = "some data";
        $client->execute($req);

        $mock->shouldHaveReceived('setOpt', [CURLOPT_URL, "http://localhost/path"])->once();
        $mock->shouldHaveReceived('setOpt', [CURLOPT_CUSTOMREQUEST, "POST"])->once();
        $mock->shouldHaveReceived('setOpt', [CURLOPT_HTTPHEADER, $this->serializeHeaders($req->headers)])->once();
        $mock->shouldHaveReceived('setOpt', [CURLOPT_POSTFIELDS, "some data"])->once();
        $mock->shouldHaveReceived('setOpt', [CURLOPT_RETURNTRANSFER, 1])->once();
        $mock->shouldHaveReceived('setOpt', [CURLOPT_HEADER, 1])->once();
        $mock->shouldHaveReceived('close')->once();
    }

    public function testExecute_setsSSLIfBaseUrlIsHttps()
    {
        $environment = new DevelopmentEnvironment("https://localhost");
        $mock = \Mockery::mock(new MockCurl(200))->makePartial();
        $client = new MockHttpClient($environment, $mock);

        $req = new HttpRequest("/path", "POST");
        $req->body[] = "some data";
        $req->headers["Content-Type"] = "text/plain";
        $client->execute($req);

        $mock->shouldHaveReceived('setOpt', [CURLOPT_SSL_VERIFYPEER, true])->once();
        $mock->shouldHaveReceived('setOpt', [CURLOPT_SSL_VERIFYHOST, 2])->once();
    }

    public function testExecute_setsUserAgentIfNotSet()
    {
        $environment = new DevelopmentEnvironment("http://localhost");
        $mock = \Mockery::mock(new MockCurl(200))->makePartial();
        $client = new MockHttpClient($environment, $mock);

        $req = new HttpRequest("/path", "POST");
        $client->execute($req);

        $mock
            ->shouldHaveReceived('setOpt')
            ->with(CURLOPT_HTTPHEADER, \Mockery::on(function ($argument) use ($client) {
                return $client->userAgent() === $this->deserializeHeaders($argument)['User-Agent'];
            }));
    }

    public function testExecute_doesNotSetUserAgentIfAlreadySet()
    {
        $environment = new DevelopmentEnvironment("http://localhost");
        $mock = \Mockery::mock(new MockCurl(200))->makePartial();
        $client = new MockHttpClient($environment, $mock);

        $req = new HttpRequest("/path", "POST");
        $req->headers["User-Agent"] = "Example user-agent";
        $client->execute($req);

        $mock
            ->shouldHaveReceived('setOpt', [CURLOPT_HTTPHEADER, \Mockery::on(function ($argument) {
                return "Example user-agent" === $this->deserializeHeaders($argument)['User-Agent'];
            })]);
    }

    public function testExecute_setsHeadersInRequest()
    {
        $environment = new DevelopmentEnvironment("http://localhost");
        $mock = \Mockery::mock(new MockCurl(200))->makePartial();
        $client = new MockHttpClient($environment, $mock);

        $req = new HttpRequest("/path", "POST");
        $req->headers["Custom-Header"] = "Custom value";
        $client->execute($req);

        $mock
            ->shouldHaveReceived('setOpt', [CURLOPT_HTTPHEADER, \Mockery::on(function ($argument) {
                return "Custom value" === $this->deserializeHeaders($argument)['Custom-Header'];
            })]);
    }

    public function testExecute_setsHeadersFromResponse()
    {
        $environment = new DevelopmentEnvironment("http://localhost");
        $responseHeaders = [
            "Some-key" => "Some value",
            "Content-Type" => "text/plain"
        ];
        $mock = \Mockery::mock(new MockCurl(200, "Response body", $responseHeaders))->makePartial();
        $client = new MockHttpClient($environment, $mock);

        $req = new HttpRequest("/path", "POST");
        $res = $client->execute($req);

        $this->assertEquals("Some value", $res->headers["Some-key"]);
    }

    public function testExecute_defersToSubclassToSerialize()
    {
        $environment = new DevelopmentEnvironment("http://localhost");
        $mock = \Mockery::mock(new MockCurl(200))->makePartial();
        $client = new WhiteSpaceRemovingSerializingClient($environment, $mock);

        $req = new HttpRequest("/path", "POST");
        $req->body[] = "some data here";
        $client->execute($req);

        $mock->shouldHaveReceived('setOpt', [CURLOPT_POSTFIELDS, "somedatahere"])->once();
    }

    public function testExecute_defersToSubclassToDeserialize()
    {
        $environment = new DevelopmentEnvironment("http://localhost");
        $mock = \Mockery::mock(new MockCurl(200, "some junk data", ["myKey" => "myValue"]))->makePartial();
        $client = new FancyResponseDeserializingClient($environment, $mock);

        $req = new HttpRequest("/path", "POST");
        $res = $client->execute($req);

        $this->assertEquals('{"myJSON": "isBetterThanYourJSON"}', $res->result);
    }

    public function testExecute_throwsForNon200LevelResponse()
    {
        $environment = new DevelopmentEnvironment("http://localhost");
        $responseHeaders = [
            "Debug-Id" => "Debug Data",
            "Content-Type" => "text/plain"
        ];
        $mock = \Mockery::mock(new MockCurl(400, "Response body", $responseHeaders))->makePartial();
        $client = new MockHttpClient($environment, $mock);

        $req = new HttpRequest("/path", "POST");
        try {
            $client->execute($req);
            $this->fail("expected execute to throw");
        } catch (HttpException $e) {
            $this->assertEquals(400, $e->response->statusCode);
            $this->assertArraySubset(["Debug-Id" => "Debug Data"], $e->response->headers);
            $this->assertEquals("Response body", $e->response->result);
        } catch (\Exception $e) {
            echo($e);
            $this->fail("execute threw non-HttpException");
        }
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

    private function deserializeHeaders($headers)
    {
        $separatedHeaders = [];
        foreach ($headers as $header) {
            if (!empty($header)) {
                list($key, $val) = explode(":", $header);
                $separatedHeaders[$key] = trim($val);
            }
        }
        return $separatedHeaders;
    }
}

class MockHttpClient extends HttpClient
{
    public function __construct(Environment $environment, Curl $curl = null)
    {
        parent::__construct($environment);
        if ($curl) {
            $this->setCurl($curl);
        }
    }
}

class BasicInjector implements Injector
{
    public function inject($httpRequest)
    {
        $httpRequest->path = "/some-other-path";
    }
}

class WhiteSpaceRemovingSerializingClient extends MockHttpClient
{
    public function serializeRequest($request)
    {
        $str = "";
        foreach ($request->body as $body) {
            $str .= str_replace(' ', '', $body);
        }
        return $str;
    }
}

class FancyResponseDeserializingClient extends MockHttpClient
{
    public function deserializeResponse($responseBody, $headers)
    {
        return '{"myJSON": "isBetterThanYourJSON"}';
    }
}

class DevelopmentEnvironment implements Environment
{
    /**
     * @var string
     */
    private $baseUrl;

    public function __construct($baseUrl)
    {
        $this->baseUrl = $baseUrl;
    }

    public function baseUrl()
    {
        return $this->baseUrl;
    }
}

class MockCurl extends Curl
{
    private $statusCode;
    private $data;
    private $headers;
    private $errorCode;
    private $error;
    private $reqHeaders;

    public function __construct($statusCode, $data = null, $headers = [], $errorCode = 0, $error = null)
    {
        $this->statusCode = $statusCode;
        $this->data = $data;
        $this->headers = $headers;
        $this->errorCode = $errorCode;
        $this->error = $error;
    }

    public function setOpt($option, $value)
    {
        switch ($option) {
            case CURLOPT_HTTPHEADER:
                $this->reqHeaders = $value;
        }
        return $this;
    }

    public function init()
    {
        return $this;
    }

    public function close()
    {
        // do nothing
    }

    public function getInfo($option = null)
    {
        if ($option != null) {
            return $this->statusCode;
        }
        return curl_getinfo($this->curl);
    }

    public function exec()
    {
        $response = "HTTP/1.1 " . $this->statusCode . " Status Message\r\n";
        $serializedHeaders = [];
        foreach ($this->headers as $key => $value) {
            $serializedHeaders[] = $key . ":" . $value;
        }
        $response .= implode("\r\n", $serializedHeaders);
        $response .= "\r\n\r\n";
        if (!is_null($this->data)) {
            $response .= $this->data;
        }

        return $response;
    }

    public function errNo()
    {
        return $this->errorCode;
    }

    public function error()
    {
        return $this->error;
    }
}
