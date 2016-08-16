<?php

class TestCase extends \PHPUnit_Framework_TestCase
{
    protected $baseUri = 'http://localhost:8083/';

    /**
     * @var GuzzleHttp\Client
     */
    protected $client;

    public function setUp()
    {
        $this->client = new GuzzleHttp\Client(['base_uri' => $this->baseUri]);
    }
}
