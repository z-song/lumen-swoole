<?php

use Illuminate\Http\Response;

class HttpTest extends TestCase
{
    public function testGetIndex()
    {
        $result = $this->client->get('/');

        $app = new \Laravel\Lumen\Application();

        $this->assertEquals(Response::HTTP_OK, $result->getStatusCode());
        $this->assertEquals($app->version(), $result->getBody()->getContents());
    }

    public function testGetUrl()
    {
        $result = $this->client->get('test1');

        $this->assertEquals(Response::HTTP_OK, $result->getStatusCode());
        $this->assertEquals('hello world', $result->getBody()->getContents());
    }

    public function testGetWithParemeter()
    {
        $parameters = ['foo' => 'bar'];

        $result = $this->client->get('test2', ['query' => $parameters]);

        $this->assertEquals(Response::HTTP_OK, $result->getStatusCode());
        $this->assertEquals(\GuzzleHttp\json_encode($parameters), $result->getBody()->getContents());
    }

    public function testMethodNotAllowed()
    {
        try {
            $this->client->post('test2');
        } catch (GuzzleHttp\Exception\ClientException $e) {
            $this->assertEquals(Response::HTTP_METHOD_NOT_ALLOWED, $e->getResponse()->getStatusCode());
        }
    }

    public function testNotFound()
    {
        try {
            $this->client->get('notfound');
        } catch (GuzzleHttp\Exception\ClientException $e) {
            $this->assertEquals(Response::HTTP_NOT_FOUND, $e->getResponse()->getStatusCode());
        }
    }

    public function testGetWithCookie()
    {
        $cookie = ['foo' => 'bar', 'baz' => 'fiz'];

        $jar = \GuzzleHttp\Cookie\CookieJar::fromArray($cookie, '127.0.0.1');

        $result = $this->client->get('test3', ['cookies' => $jar]);

        $this->assertEquals(Response::HTTP_OK, $result->getStatusCode());
        $this->assertEquals(\GuzzleHttp\json_encode($cookie), $result->getBody()->getContents());
    }

    public function testPost()
    {
        $parameters = ['foo' => 'bar'];

        $result = $this->client->post('test4', ['query' => $parameters]);

        $this->assertEquals(Response::HTTP_OK, $result->getStatusCode());
        $this->assertEquals(\GuzzleHttp\json_encode($parameters), $result->getBody()->getContents());
    }

    public function testPostWithCookie()
    {
        $cookie = ['foo' => 'bar', 'baz' => 'fiz'];

        $jar = \GuzzleHttp\Cookie\CookieJar::fromArray($cookie, '127.0.0.1');

        $result = $this->client->post('test5', ['cookies' => $jar]);

        $this->assertEquals(Response::HTTP_OK, $result->getStatusCode());
        $this->assertEquals(\GuzzleHttp\json_encode($cookie), $result->getBody()->getContents());
    }

    public function testGetWithHeader()
    {
        $header = ['foo' => 'bar'];

        $result = $this->client->get('test6', ['headers' => $header]);

        $this->assertEquals(Response::HTTP_OK, $result->getStatusCode());
        $this->assertEquals('bar', $result->getBody()->getContents());
    }

    public function testGetWithAuth()
    {
        $credentials = ['username', 'password'];

        $result = $this->client->get('test7', ['auth' => $credentials]);

        $this->assertEquals(Response::HTTP_OK, $result->getStatusCode());
        $this->assertEquals(\GuzzleHttp\json_encode($credentials), $result->getBody()->getContents());
    }

    public function testUploadFile()
    {
        $file = __DIR__.'/app.php';
        $fileName = 'app.php';

        $result = $this->client->post('upload', [
            'multipart' => [
                [
                    'name'     => 'file',
                    'filename' => $fileName,
                    'contents' => fopen($file, 'r'),
                ],
            ],
        ]);

        $this->assertEquals(Response::HTTP_OK, $result->getStatusCode());
        $this->assertEquals(\GuzzleHttp\json_encode([filesize($file), $fileName]), $result->getBody()->getContents());
    }

    public function testResponseHeader()
    {
        $result = $this->client->get('header');

        $this->assertEquals(Response::HTTP_OK, $result->getStatusCode());

        $this->assertEquals(['hello world'], $result->getHeader('foo'));
    }

    public function testResponseCookie()
    {
        $result = $this->client->get('cookie');

        $this->assertEquals(Response::HTTP_OK, $result->getStatusCode());
        $this->assertContains('hello world', implode('', $result->getHeader('Set-Cookie')));
    }

    public function testStopServer()
    {
        $this->stopServer();

        $this->setExpectedException(GuzzleHttp\Exception\RequestException::class);
        $this->client->get('test1');
    }

    public function testStartServer()
    {
        $this->startServer();

        usleep(500);

        $result = $this->client->get('test1');

        $this->assertEquals(Response::HTTP_OK, $result->getStatusCode());
        $this->assertEquals('hello world', $result->getBody()->getContents());
    }

    public function testRestartServer()
    {
        $this->stopServer();
        $this->startServer();
        usleep(500);

        $result = $this->client->get('test1');

        $this->assertEquals(Response::HTTP_OK, $result->getStatusCode());
        $this->assertEquals('hello world', $result->getBody()->getContents());
    }

    protected function stopServer()
    {
        $pid = $this->getPid();

        posix_kill($pid, SIGTERM);
        usleep(500);
        posix_kill($pid, SIGKILL);
        unlink($this->getPidFile());
    }

    protected function startServer()
    {
        $appPath = __DIR__.'/app.php';

        exec(__DIR__."/../bin/lumen-swoole -s $appPath -d");
    }

    protected function getPid()
    {
        return file_get_contents($this->getPidFile());
    }

    protected function getPidFile()
    {
        $app = require __DIR__.'/app.php';

        return $app->storagePath('lumen-swoole.pid');
    }
}
