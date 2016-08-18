<?php

class ApplicationTest extends TestCase
{
    public function testSingleton()
    {
        $result = $this->client->get('singleton');

        $this->assertEquals('world', $result->getBody()->getContents());

        $result = $this->client->get('singleton1');

        $this->assertEquals('false', $result->getBody()->getContents());
    }
}
