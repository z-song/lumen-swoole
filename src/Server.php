<?php

namespace Encore\LumenSwoole;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use swoole_http_server as HttpServer;

class Server
{
    const VERSION = 'lumen-swoole 0.1.0';

    /**
     * @var \Laravel\Lumen\Application
     */
    protected $app;

    /**
     * @var string
     */
    protected $host = '127.0.0.1';

    /**
     * @var int
     */
    protected $port = 8083;

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
     * @var array
     */
    public static $validServerOptions = [
        'reactor_num',
        'worker_num',
        'max_request',
        'max_conn',
        'task_worker_num',
        'task_ipc_mode',
        'task_max_request',
        'task_tmpdir',
        'dispatch_mode',
        'message_queue_key',
        'daemonize',
        'backlog',
        'log_file',
        'log_level',
        'heartbeat_check_interval',
        'heartbeat_idle_time',
        'open_eof_check',
        'open_eof_split',
        'package_eof',
        'open_length_check',
        'package_length_type',
        'package_max_length',
        'open_cpu_affinity',
        'cpu_affinity_ignore',
        'open_tcp_nodelay',
        'tcp_defer_accept',
        'ssl_cert_file',
        'ssl_method',
        'user',
        'group',
        'chroot',
        'pipe_buffer_size',
        'buffer_output_size',
        'socket_buffer_size',
        'enable_unsafe_event',
        'discard_timeout_request',
        'enable_reuse_port',
        'ssl_ciphers',
        'enable_delay_receive',
    ];

    /**
     * Create a new Server instance.
     *
     * @param string $host
     * @param int    $port
     */
    public function __construct($host = '127.0.0.1', $port = 8083)
    {
        $this->host = $host;
        $this->port = $port;
    }

    /**
     * Set application.
     *
     * @param \Laravel\Lumen\Application $app
     *
     * @return $this
     */
    public function setApplication($app)
    {
        $this->app = $app;

        return $this;
    }

    /**
     * Get application.
     *
     * @return \Laravel\Lumen\Application
     */
    public function getApplication()
    {
        $this->resolveApplication();

        return $this->app;
    }

    /**
     * Resolve application.
     *
     * @return void
     */
    protected function resolveApplication()
    {
        if (!$this->app) {
            $this->app = require $this->basePath('bootstrap/app.php');
        }
    }

    /**
     * Get the base path for the application.
     *
     * @param string|null $path
     *
     * @return string
     */
    public function basePath($path = null)
    {
        return getcwd().($path ? '/'.$path : $path);
    }

    /**
     * Initialize the server.
     *
     * @return $this
     */
    public function initHttpServer()
    {
        if ($this->httpServer) {
            return $this;
        }

        $this->httpServer = new HttpServer($this->host, $this->port);

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

        $this->resolveApplication();

        $this->pidFile = $this->app->storagePath('lumen-swoole.pid');

        if ($this->serverIsRunning()) {
            throw new \Exception('The server is already running.');
        }

        if (!empty($this->options)) {
            $this->httpServer->set($this->options);
        }

        $this->httpServer->start();
    }

    /**
     * Determine if server is running.
     *
     * @return bool
     */
    public function serverIsRunning()
    {
        if (!file_exists($this->pidFile)) {
            return false;
        }

        $pid = file_get_contents($this->pidFile);

        return (bool) posix_getpgid($pid);
    }

    /**
     * Set http server options.
     *
     * @param array $options
     *
     * @return $this
     */
    public function options($options = [])
    {
        $this->options = array_only($options, static::$validServerOptions);

        return $this;
    }

    /**
     * On request callback.
     *
     * @param \swoole_http_request  $request
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
        file_put_contents($this->pidFile, $server->master_pid);
    }

    /**
     * Server shutdown event callback.
     */
    public function onShutdown()
    {
        unlink($this->pidFile);
    }

    /**
     * Response handler.
     *
     * @param \swoole_http_response $swooleResponse
     * @param Response              $response
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
