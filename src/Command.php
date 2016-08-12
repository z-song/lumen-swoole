<?php

namespace Encore\LumenSwoole;

class Command
{
    public static function main($argv)
    {
        $command = new static;

        return $command->run($argv);
    }

    public function run($argv)
    {
        if ($action = $this->getAction($argv)) {
            $this->handleAction($action);

            return ;
        }

        $arguments = $this->parseArguments($argv);
        $options = [];

        if (! empty($arguments)) {
            $options = $this->handleArguments($arguments);
        }

        $server = new Server();

        $server->options($options)->run();
    }

    public function getAction($argv)
    {
        if (in_array($argv[1], ['stop', 'reload', 'restart'])) {
            return $argv[1];
        }

        return null;
    }

    public function handleAction($action)
    {
        if ($action === 'stop') {
            return $this->stop();
        }

        if ($action === 'reload') {
            return $this->reload();
        }

        if ($action === 'restart') {
            return $this->restart();
        }

        return ;
    }

    public function parseArguments($argv)
    {
        $arguments = array_slice($argv, 1);

        $options = [];

        foreach ($arguments as $argument) {
            if (preg_match('/^--([\w\d_]+)=([\w\d_]+)$/', $argument, $match)) {
                $options[$match[1]] = $match[2];
            }

            if (preg_match('/^(-[\w\d_]+)$/', $argument, $match)) {
                $options[$match[1]] = true;
            }
        }

        return $options;
    }

    public function handleArguments($arguments)
    {
        $options = array_only($arguments, static::validOptions());

        if (in_array('-d', $arguments)) {
            $options['daemonize'] = true;
        }

        return $options;
    }



    /**
     * Stop the server.
     *
     * @return void
     * @throws \Exception
     */
    public function stop()
    {


        posix_kill($this->getPid(), SIGTERM);

        usleep(500);

        posix_kill($this->getPid(), SIGKILL);

        unlink($this->pidFile);
    }

    /**
     * Reload the server.
     *
     * @return void
     * @throws \Exception
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
        $this->stop();

        usleep(2000);

        $this->options['daemonize'] = true;

        $this->start();
    }

    /**
     * Get process identifier of this server.
     *
     * @return bool|string
     * @throws \Exception
     */
    protected function getPid()
    {
        if (!file_exists($this->pidFile)) {
            throw new \Exception('Server not running.');
        }

        $pid = file_get_contents($this->pidFile);

        if (posix_getpgid($pid)) {
            return $pid;
        }

        unlink($this->pidFile);

        return false;
    }

    public static function validOptions()
    {
        return [
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
    }
}
