<?php
namespace Test\Unit;

use BraintreeHttp;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Tests\Server;

class HttpClientTest extends TestCase
{
    /**
     * @var BraintreeHttp\HttpClient
     */
    private $client;

    /**
     * @var BraintreeHttp\Environment
     */
    private $environment;

    public static function setUpBeforeClass()
    {
        Server::start();
    }

    /**
     * @before
     */
    public function setup()
    {
        Server::flush();

        $this->environment = new DevelopmentEnvironment(Server::$url);
        $this->client = new BraintreeHttp\HttpClient($this->environment);
    }

    /**
     * @after
     */
    public function teardown()
    {

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

    public function testExecute_usesInjectorsToModifyRequest()
    {
        Server::enqueue([
            new Response(200)
        ]);

        $injector = new BasicInjector();
        $this->client->addInjector($injector);

        $req = new BraintreeHttp\HttpRequest("/path", "GET");

        $this->client->execute($req);

        $this->assertEquals("/some-other-path", $req->path);
    }

    public function testExecute_setsUserAgentIfNotSet()
    {
        Server::enqueue([
            new Response(200)
        ]);

        $req = new BraintreeHttp\HttpRequest("/path", "GET");

        $this->client->execute($req);

        $this->assertEquals($this->client->userAgent(), Server::received()[0]->getHeader("User-Agent")[0]);
    }

    public function testExecute_doesNotSetUserAgentIfAlreadySet()
    {
        Server::enqueue([
            new Response(200)
        ]);

        $req = new BraintreeHttp\HttpRequest("/path", "GET");
        $req->headers["User-Agent"] = "Example user-agent";

        $this->client->execute($req);

        $this->assertEquals("Example user-agent", Server::received()[0]->getHeader("User-Agent")[0]);
    }

    public function testExecute_usesBodyInRequestIfPresent()
    {
        Server::enqueue([
            new Response(200)
        ]);

        $req = new BraintreeHttp\HttpRequest("/path", "POST");
        $req->body[] = "some data";

        $res = $this->client->execute($req);

        $received = Server::received()[0];

        $this->assertContains("some data", $received->getBody()->getContents());
    }

    public function testExecute_doesNotUseBodyIfNotPresent()
    {
        Server::enqueue([
            new Response(200)
        ]);

        $req = new BraintreeHttp\HttpRequest("/path", "POST");

        $this->client->execute($req);

        $this->assertEquals(0, strlen(Server::received()[0]->getBody()->getContents()));
    }

    public function testExecute_setsHeadersInRequest()
    {
        Server::enqueue([
            new Response(200)
        ]);

        $req = new BraintreeHttp\HttpRequest("/path", "POST");
        $req->headers["Custom-Header"] = "Custom value";

        $this->client->execute($req);

        $this->assertEquals("Custom value", Server::received()[0]->getHeader("Custom-Header")[0]);
    }

    public function testExecute_setsHeadersFromResponse()
    {
        Server::enqueue([
            new Response(200, ["Some-key" => "Some value"])
        ]);

        $req = new BraintreeHttp\HttpRequest("/path", "POST");

        $res = $this->client->execute($req);

        $this->assertEquals("Some value", $res->headers["Some-key"]);
    }

    public function testExecute_parses200LevelResponse()
    {
        Server::enqueue([
            new Response(200, [],'')
        ]);

        $req = new BraintreeHttp\HttpRequest("/path", "POST");

        $res = $this->client->execute($req);

        $this->assertEquals(200, $res->statusCode);
    }

    public function testExecute_throwsforNon200LevelResponse()
    {
        Server::enqueue([
            new Response(400, ["Response-Header" => "Debug Value"],"Response body")
        ]);

        $req = new BraintreeHttp\HttpRequest("/path", "POST");

        try
        {
            $res = $this->client->execute($req);
            $this->fail("expected execute to throw");
        }
        catch (BraintreeHttp\HttpException $e)
        {
            $this->assertEquals(400, $e->response->statusCode);
            $this->assertArraySubset(["Response-Header" => "Debug Value"], $e->response->headers);
            $this->assertEquals("Response body", $e->response->body);
        }
        catch (\Exception $e)
        {
            echo($e);
            $this->fail("execute threw non-HttpException");
        }
    }

    public function testExecute_defersToSubclassToSerialize()
    {

    }

    public function testExecute_defersToSubclassToDeserialize()
    {
        Server::enqueue([
            new Response(200, ["myKey" => "myValue"], "some junk data")
        ]);

        $req = new BraintreeHttp\HttpRequest("/path", "POST");

        $client = new FancyResponseDeserializingClient($this->environment);
        $client->execute($req);
    }
}

class DevelopmentEnvironment implements BraintreeHttp\Environment
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

class BasicInjector implements BraintreeHttp\Injector
{
    public function inject($httpRequest)
    {
        $httpRequest->path = "/some-other-path";
    }
}

class FancyResponseDeserializingClient extends BraintreeHttp\HttpClient
{
    public function deserializeResponse($responseBody, $headers)
    {
        if ($headers["myKey"] == "myValue")
        {
            return '{"myJSON": "isBetterThanYourJSON"}';
        }
        return parent::deserializeResponse($responseBody, $headers);
    }
}