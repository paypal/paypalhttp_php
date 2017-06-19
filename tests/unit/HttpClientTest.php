<?php
namespace Test\Unit;

use BraintreeHttp;
use PHPUnit\Framework\TestCase;

class HttpClientTest extends TestCase
{
    /**
     * @var BraintreeHttp\HttpClient
     */
    public $client;

    /**
     * @before
     */
    public function setupHttpClient()
    {
        $this->client = new BraintreeHttp\HttpClient(new BasicEnvironment());
    }

    public function testAddInjector_addsInjectorToInjectorList()
    {
        $inj = new BasicInjector();
        $this->client->addInjector($inj);

        $this->assertContains($inj, $this->client->injectors);
    }

    public function testAddsMultipleInjectors_addsMultipleInjectorsToInjectorList()
    {
        $inj1 = new BasicInjector();
        $this->client->addInjector($inj1);

        $inj2 = new BasicInjector();
        $this->client->addInjector($inj2);

        $this->assertArraySubset([$inj1, $inj2], $this->client->injectors);
    }

    public function testExecute_callsAllInjectors()
    {
        $injector = new BasicInjector();
        $this->client->addInjector($injector);

        $req = new BraintreeHttp\HttpRequest("/some-path", "GET");

        $this->client->execute($req);

        $this->assertEquals("/some-other-path", $req->path);
    }

    public function testExecute_setsUserAgentIfNotSet()
    {
        $req = new BraintreeHttp\HttpRequest("/some-path", "GET");

        $this->client->execute($req);

        $this->assertEquals($this->client->userAgent(), $req->headers["User-Agent"]);
    }

    public function testExecute_doesNotSetUserAgentIfAlreadySet()
    {
        $req = new BraintreeHttp\HttpRequest("/some-path", "GET");
        $req->headers["User-Agent"] = "Example user-agent";

        $this->client->execute($req);

        $this->assertEquals("Example user-agent", $req->headers["User-Agent"]);
    }

    public function testExecute_doesAThing()
    {
        $req = new BraintreeHttp\HttpRequest("/", "GET");

        $this->client->execute($req);
    }
}

class BasicEnvironment implements BraintreeHttp\Environment
{
    public function baseUrl()
    {
        return "http://google.com;";
    }
}

class BasicInjector implements BraintreeHttp\Injector
{
    public function inject($httpRequest)
    {
        $httpRequest->path = "/some-other-path";
    }
}