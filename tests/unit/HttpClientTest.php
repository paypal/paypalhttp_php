<?php
namespace Test\Unit;

use BraintreeHttp;
use PHPUnit\Framework\TestCase;

class HttpClientTest extends TestCase
{
    /**
     * @var BraintreeHttp\HttpClient
     */
    public $basicClient;

    /**
     * @var BraintreeHttp\HttpClient
     */
    public $googleClient;

    /**
     * @before
     */
    public function setupHttpClient()
    {
        $this->basicClient = new BraintreeHttp\HttpClient(new BasicEnvironment());
        $this->googleClient = new BraintreeHttp\HttpClient(new GoogleEnvironment());
    }

    public function testAddInjector_addsInjectorToInjectorList()
    {
        $inj = new BasicInjector();
        $this->basicClient->addInjector($inj);

        $this->assertContains($inj, $this->basicClient->injectors);
    }

    public function testAddsMultipleInjectors_addsMultipleInjectorsToInjectorList()
    {
        $inj1 = new BasicInjector();
        $this->basicClient->addInjector($inj1);

        $inj2 = new BasicInjector();
        $this->basicClient->addInjector($inj2);

        $this->assertArraySubset([$inj1, $inj2], $this->basicClient->injectors);
    }

    public function testExecute_callsAllInjectors()
    {
        $injector = new BasicInjector();
        $this->basicClient->addInjector($injector);

        $req = new BraintreeHttp\HttpRequest("/some-path", "GET");

        $this->basicClient->execute($req);

        $this->assertEquals("/some-other-path", $req->path);
    }

    public function testExecute_setsUserAgentIfNotSet()
    {
        $req = new BraintreeHttp\HttpRequest("/some-path", "GET");

        $this->basicClient->execute($req);

        $this->assertEquals($this->basicClient->userAgent(), $req->headers["User-Agent"]);
    }

    public function testExecute_doesNotSetUserAgentIfAlreadySet()
    {
        $req = new BraintreeHttp\HttpRequest("/some-path", "GET");
        $req->headers["User-Agent"] = "Example user-agent";

        $this->basicClient->execute($req);

        $this->assertEquals("Example user-agent", $req->headers["User-Agent"]);
    }

    public function testExecute_setsTheVerb()
    {
        $req = new BraintreeHttp\HttpRequest("/", "GET");

        $this->basicClient->execute($req);

        $this->assertEquals("GET", $req->verb);
    }

    public function testExecute_setsThePath()
    {
        $req = new BraintreeHttp\HttpRequest("/path", "GET");

        $this->basicClient->execute($req);

        $this->assertEquals("/path", $req->path);
    }

    public function testExecute_getsATwoHundredBackFromGETtingGoogle()
    {
        $req = new BraintreeHttp\HttpRequest("/", "GET");

        $res = $this->googleClient->execute($req);

        $this->assertEquals(true, $res->successful);
        $this->assertEquals(200, $res->code);
    }
}

class GoogleEnvironment implements BraintreeHttp\Environment
{
    public function baseUrl()
    {
        return "https://www.google.com";
    }
}

class BasicEnvironment implements BraintreeHttp\Environment
{
    public function baseUrl()
    {
        return "https://localhost";
    }
}

class BasicInjector implements BraintreeHttp\Injector
{
    public function inject($httpRequest)
    {
        $httpRequest->path = "/some-other-path";
    }
}