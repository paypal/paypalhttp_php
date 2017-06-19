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
        $this->client = new BraintreeHttp\HttpClient("SANDBOX");
    }

    public function testAddInjector_addsInjectorToInjectorList()
    {
        $inj = "i_am_an_injector";
        $this->client->addInjector($inj);

        $this->assertContains($inj, $this->client->injectors);
    }

    public function testAddsMultipleInjectors_addsMultipleInjectorsToInjectorList()
    {
        $inj1 = "i_am_the first_injector";
        $this->client->addInjector($inj1);

        $inj2 = "i_am_the_second_injector";
        $this->client->addInjector($inj2);

        $this->assertArraySubset([$inj1, $inj2], $this->client->injectors);
    }
}