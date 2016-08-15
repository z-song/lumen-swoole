<?php

class HttpTest extends TestCase
{
    public function testGetIndex()
    {
        $client = new GuzzleHttp\Client();
        $result = $client->get($this->baseUrl);

        $app = new \Laravel\Lumen\Application();
        $this->assertEquals($app->version(), $result->getBody()->getContents());
    }

//    public function testGetTest()
//    {
//        $client = new GuzzleHttp\Client();
//        $result = $client->get($this->baseUrl);
//
//        $this->assertEquals('hello world', $result->getBody()->getContents());
//    }
}
