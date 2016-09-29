<?php

namespace Encore\LumenSwoole;

use Error;
use ErrorException;
use Laravel\Lumen\Exceptions\Handler;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Debug\Exception\FatalErrorException;
use Symfony\Component\Debug\Exception\FatalThrowableError;

/**
 * Class Command.
 */
class Command
{
    /**
     * Pid file.
     *
     * @var string
     */
    protected $pidFile;

    /**
     * Command options.
     *
     * @var array
     */
    protected $options = [];

    /**
     * Server host.
     *
     * @var string
     */
    protected $host = '127.0.0.1';

    /**
     * Server port.
     *
     * @var int
     */
    protected $port = 8083;

    /**
     * Application bootstrap file.
     *
     * @var string
     */
    protected $bootstrap = 'bootstrap/app.php';

    /**
     * Http server options.
     *
     * @var array
     */
    protected $serverOptions = [];

    /**
     * Create a new Command instance.
     */
    public function __construct()
    {
        $this->registerErrorHandling();
    }

    /**
     * Main access.
     *
     * @param $argv
     *
     * @return mixed
     */
    public static function main($argv)
    {
        $command = new static();

        return $command->run($argv);
    }

    /**
     * Run up the server.
     *
     * @param string $argv
     *
     * @throws \Exception
     */
    public function run($argv)
    {
        if ($this->handleAction($argv)) {
            return;
        }

        if (!$this->handleArguments()) {
            return;
        }

        echo "Swoole http server started on http://{$this->host}:{$this->port}/\r\n";

        $server = new Server($this->host, $this->port);
        $server->setApplication(require $this->bootstrap);

        $server->options($this->serverOptions)->run();
    }

    /**
     * Handle command action.
     *
     * @param array $argv
     *
     * @return bool
     */
    public function handleAction($argv)
    {
        if (count($argv) < 2) {
            return false;
        }

        if (in_array($argv[1], ['stop', 'reload', 'restart'])) {
            call_user_func([$this, $argv[1]]);

            return true;
        }

        return false;
    }

    /**
     * Handle Command arguments.
     *
     * @return bool
     */
    public function handleArguments()
    {
        $serverOptions = array_map(function ($option) {
            return "$option:";
        }, Server::$validServerOptions);

        $longOptions = array_merge(['host:', 'port:', 'help', 'version'], $serverOptions);

        $options = getopt('dvp:h::s:', $longOptions);

        foreach ($options as $option => $value) {
            switch ($option) {
                case 'h':
                case 'host':
                    if ($value) {
                        $this->host = $value;
                    } else {
                        $this->usage();

                        return false;
                    }
                    break;

                case 'help':
                    $this->usage();

                    return false;

                case 'p':
                case 'port':
                    if ($value) {
                        $this->port = (int) $value;
                    }
                    break;

                case 's':
                    if ($value) {
                        $this->bootstrap = $value;
                    }
                    break;

                case 'd':
                    $this->serverOptions['daemonize'] = true;
                    break;

                case 'v':
                case 'version':
                    echo Server::VERSION, "\r\n";

                    return false;

                default:
                    if (in_array($option, Server::$validServerOptions) && $value) {
                        $this->serverOptions[$option] = $value;
                    }
                    break;
            }
        }

        return true;
    }

    /**
     * Show usage.
     */
    public function usage()
    {
        $this->printVersionString();

        echo <<<'EOT'

Usage: vendor/bin/lumen-swoole {stop|restart|reload}

  -h <hostname>      Server hostname (default: 127.0.0.1).
  -p <port>          Server port (default: 6379).
  -s <script>        Application script.
  -d <daemon>        Run server in daemon mode.
  -v <version>       Output version and exit.

  --host             Server hostname (default: 127.0.0.1).
  --port             Server port (default: 6379).
  --help             Output this help and exit.
  --version          Output version and exit.

Examples:
  vendor/bin/lumen-swoole -d
  vendor/bin/lumen-swoole -h 127.0.0.1 -p 80 -d
  vendor/bin/lumen-swoole -h 127.0.0.1 -p 80 -d
  vendor/bin/lumen-swoole -s path/to/bootstrap/script.php

  vendor/bin/lumen-swoole restart
  vendor/bin/lumen-swoole reload
  vendor/bin/lumen-swoole restart

Other options please see http://wiki.swoole.com/wiki/page/274.html.

EOT;
    }

    /**
     * Print version string.
     */
    public function printVersionString()
    {
        echo Server::VERSION, "\r\n";
    }

    /**
     * Stop the server.
     *
     * @throws \Exception
     *
     * @return void
     */
    public function stop()
    {
        $pid = $this->getPid();

        echo "Server is stopping...\r\n";

        posix_kill($pid, SIGTERM);

        usleep(500);

        posix_kill($pid, SIGKILL);

        unlink($this->pidFile);
    }

    /**
     * Reload the server.
     *
     * @throws \Exception
     *
     * @return void
     */
    public function reload()
    {
        posix_kill($this->getPid(), SIGUSR1);
    }

    /**
     * Restart the server.
     *
     * @throws \Exception
     *
     * @return void
     */
    public function restart()
    {
        $pid = $this->getPid();

        $cmd = exec("ps -p $pid -o args | grep lumen-swoole");

        if (empty($cmd)) {
            throw new \Exception('Cannot find server process.');
        }

        $this->stop();

        usleep(2000);

        echo "Server is starting...\r\n";

        exec($cmd);
    }

    /**
     * Get process identifier of this server.
     *
     * @throws \Exception
     *
     * @return string|false
     */
    protected function getPid()
    {
        $app = require $this->bootstrap;

        $this->pidFile = $app->storagePath('lumen-swoole.pid');

        if (!file_exists($this->pidFile)) {
            throw new \Exception('The Server is not running.');
        }

        $pid = file_get_contents($this->pidFile);

        if (posix_getpgid($pid)) {
            return $pid;
        }

        unlink($this->pidFile);

        return false;
    }

    /**
     * Set the error handling for the application.
     *
     * @return void
     */
    protected function registerErrorHandling()
    {
        error_reporting(-1);

        set_error_handler(function ($level, $message, $file = '', $line = 0) {
            if (error_reporting() & $level) {
                throw new ErrorException($message, 0, $level, $file, $line);
            }
        });

        set_exception_handler(function ($e) {
            $this->handleUncaughtException($e);
        });

        register_shutdown_function(function () {
            $this->handleShutdown();
        });
    }

    /**
     * Handle an uncaught exception instance.
     *
     * @param \Exception $e
     *
     * @return void
     */
    protected function handleUncaughtException($e)
    {
        if ($e instanceof Error) {
            $e = new FatalThrowableError($e);
        }

        (new Handler())->renderForConsole(new ConsoleOutput(), $e);
    }

    /**
     * Handle the application shutdown routine.
     *
     * @return void
     */
    protected function handleShutdown()
    {
        if (!is_null($error = error_get_last()) && $this->isFatalError($error['type'])) {
            $this->handleUncaughtException(new FatalErrorException(
                $error['message'],
                $error['type'],
                0,
                $error['file'],
                $error['line']
            ));
        }
    }

    /**
     * Determine if the error type is fatal.
     *
     * @param int $type
     *
     * @return bool
     */
    protected function isFatalError($type)
    {
        $errorCodes = [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE];

        if (defined('FATAL_ERROR')) {
            $errorCodes[] = FATAL_ERROR;
        }

        return in_array($type, $errorCodes);
    }
}
