<?php

namespace Encore\LumenSwoole;

use Error;
use ErrorException;
use Laravel\Lumen\Exceptions\Handler;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Debug\Exception\FatalErrorException;
use Symfony\Component\Debug\Exception\FatalThrowableError;

class Command
{
    protected $pidFile;

    protected $options = [];

    protected $host = 'localhost';

    protected $port = 8083;

    protected $bootstrap = 'bootstrap/app.php';

    protected $serverOptions = [];

    public function __construct()
    {
        $this->registerErrorHandling();
    }

    public static function main($argv)
    {
        $command = new static();

        return $command->run($argv);
    }

    public function run($argv)
    {
        $this->handleAction($argv);

        $this->handleArguments();

        $server = new Server($this->host, $this->port);
        $server->setApplication(require $this->bootstrap);

        $server->options($this->serverOptions)->run();
    }

    /**
     * @param array $argv
     *
     * @return void
     */
    public function handleAction($argv)
    {
        if (count($argv) < 2) {
            return ;
        }

        if (in_array($argv[1], ['stop', 'reload', 'restart'])) {
            call_user_func([$this, $argv[1]]);

            exit;
        }
    }

    public function handleArguments()
    {
        $serverOptions = array_map(function ($option) {
            return "$option:";
        }, Server::$validServerOptions);

        $longOptions = array_merge(['host:', 'port:', 'help'], $serverOptions);

        $options = getopt('dp:h::s:', $longOptions);

        foreach ($options as $option => $value) {
            switch ($option) {
                case 'h':
                case 'host':
                    if ($value) {
                        $this->host = $value;
                    } else {
                        $this->usage();
                    }
                    break;

                case 'help':
                    $this->usage();
                    break;

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

                default:
                    if (in_array($option, Server::$validServerOptions) && $value) {
                        $this->serverOptions[$option] = $value;
                    }
                    break;
            }
        }

        return $options;
    }

    /**
     * Show usage.
     */
    public function usage()
    {
        exit("Usage: ./vendor/bin/lumen-swoole {stop|restart|reload}\r\n");
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
     * @return void
     */
    public function restart()
    {
        $cmd = exec('ps -eo args | grep lumen-swoole | grep -v grep | head -n 1');

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
     * @return bool|string
     */
    protected function getPid()
    {
        $this->pidFile = __DIR__.'/../../../../storage/lumen-swoole.pid';

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
     * @param  \Throwable  $e
     * @return void
     */
    protected function handleUncaughtException($e)
    {
        if ($e instanceof Error) {
            $e = new FatalThrowableError($e);
        }

        (new Handler())->renderForConsole(new ConsoleOutput, $e);
    }

    /**
     * Handle the application shutdown routine.
     *
     * @return void
     */
    protected function handleShutdown()
    {
        if (! is_null($error = error_get_last()) && $this->isFatalError($error['type'])) {
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
     * @param  int  $type
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
