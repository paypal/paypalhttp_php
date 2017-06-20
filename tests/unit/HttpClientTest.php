<?php
namespace Test\Unit;

use BraintreeHttp;
use PHPUnit\Framework\TestCase;
use InterNations\Component\HttpMock\PHPUnit\HttpMockTrait;

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

    use HttpMockTrait;

    public static function setUpBeforeClass()
    {
        static::setUpHttpMockBeforeClass(9000, "localhost");
    }

    public static function tearDownAfterClass()
    {
        static::tearDownHttpMockAfterClass();
    }

    /**
     * @before
     */
    public function setupHttpClient()
    {
        $this->environment = new DevelopmentEnvironment("http://localhost:9000");
        $this->client = new BraintreeHttp\HttpClient($this->environment);

        $this->setUpHttpMock();
    }

    /**
     * @after
     */
    public function tearDown()
    {
        $this->tearDownHttpMock();
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

    public function testExecute_usesBodyInRequestIfPresent()
    {
        $req = new BraintreeHttp\HttpRequest("/path", "POST");
        $req->body[] = "some data";

        $this->http->mock
            ->when()
                ->methodIs("POST")
                ->pathIs("/path")
            ->then()
                ->statusCode(200)
            ->end();
        $this->http->setUp();

        $res = $this->client->execute($req);
        echo $res->statusCode;

        echo count($this->http->requests);
        $receivedReq = $this->http->requests->last();

        $this->assertEquals("some data", $receivedReq->getBody());
    }

    public function testExecute_doesNotUseBodyIfNotPresent()
    {

    }

    public function textExecute_setsHeadersInRequest()
    {

    }

    public function textExecute_setsHeadersFromResponse()
    {

    }

    public function textExecute_parses200LevelResponse()
    {

    }

    public function testExecute_throwsforNon200LevelResponse()
    {

    }

    public function testExecute_defersToSubclassToSerialize()
    {

    }

    public function testExecute_defersToSubclassToDeserialize()
    {

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