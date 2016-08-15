<?php

namespace Encore\LumenSwoole;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use swoole_http_server as HttpServer;

class Server
{
    /**
     * @var \Laravel\Lumen\Application
     */
    protected $app;

    /**
     * @var string
     */
    protected $pidFile = '';

    /**
     * @var HttpServer
     */
    protected $httpServer;

    /**
     * Http server options.
     *
     * @var array
     */
    protected $options = [];

    /**
     * Create a new Server instance.
     *
     * @param \Laravel\Lumen\Application $app
     * @return Server
     */
    public function __construct($app = null)
    {
        $this->pidFile = sys_get_temp_dir().'/lumen-swoole.pid';

        $this->app = $app;
        if (is_null($app)) {
            $this->app = require $this->basePath('bootstrap/app.php');
        }
    }

    /**
     * Get the base path for the application.
     *
     * @param  string|null  $path
     * @return string
     */
    public function basePath($path = null)
    {
        return getcwd().($path ? '/'.$path : $path);
    }

    public function initHttpServer()
    {
        if ($this->httpServer) {
            return $this;
        }

        $this->httpServer = new HttpServer('127.0.0.1', 8083);

        $this->httpServer->on('Request', [$this, 'onRequest']);
        $this->httpServer->on('Start', [$this, 'onStart']);
        $this->httpServer->on('Shutdown', [$this, 'onShutdown']);

        return $this;
    }

    /**
     * Start the server.
     *
     * @return void
     */
    public function run()
    {
        $this->initHttpServer();

        if (! empty($this->options)) {
            $this->httpServer->set($this->options);
        }

        $this->httpServer->start();
    }

    /**
     * Set http server options.
     *
     * @param array $options
     * @return $this
     */
    public function options($options = [])
    {
        $this->options = $options;

        return $this;
    }

    /**
     * On request callback.
     *
     * @param \swoole_http_request $request
     * @param \swoole_http_response $response
     */
    public function onRequest($request, $response)
    {
        foreach ($request->server as $key => $value) {
            $_SERVER[strtoupper($key)] = $value;
        }

        if (property_exists($request, 'get')) {
            $_GET = $request->get;
        }

        if (property_exists($request, 'post')) {
            $_POST = $request->post;
        }

        if (property_exists($request, 'cookie')) {
            $_COOKIE = $request->cookie;
        }

        if (property_exists($request, 'files')) {
            $_FILES = $request->files;
        }

        if (property_exists($request, 'header')) {
            foreach ($request->header as $key => $value) {
                $_SERVER['HTTP_'.strtoupper($key)] = $value;
            }
        }

        $this->handleResponse($response, $this->app->dispatch(Request::capture()));
    }

    /**
     * Server start event callback.
     *
     * @param $server
     */
    public function onStart($server)
    {
        file_put_contents($this->pidFile,  $server->master_pid);
    }

    /**
     * Server shutdown event callback.
     *
     * @param $server
     */
    public function onShutdown($server)
    {
        unlink($this->pidFile);
    }

    /**
     * Response handler.
     *
     * @param \swoole_http_response $swooleResponse
     * @param Response $response
     *
     * @return void
     */
    protected function handleResponse($swooleResponse, Response $response)
    {
        $swooleResponse->status($response->getStatusCode());

        foreach ($response->headers->allPreserveCase() as $name => $values) {
            foreach ($values as $value) {
                $swooleResponse->header($name, $value);
            }
        }

        // set cookies
        foreach ($response->headers->getCookies() as $cookie) {
            $swooleResponse->rawcookie(
                $cookie->getName(),
                $cookie->getValue(),
                $cookie->getExpiresTime(),
                $cookie->getPath(),
                $cookie->getDomain(),
                $cookie->isSecure(),
                $cookie->isHttpOnly()
            );
        }

        // send content & close
        $swooleResponse->end($response->getContent());
    }
}
