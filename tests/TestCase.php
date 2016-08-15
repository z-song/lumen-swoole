<?php

class TestCase extends \PHPUnit_Framework_TestCase
{
    protected $baseUrl = 'http://localhost:8083/';

//    public function setUp()
//    {
//        exec(PHP_BINARY.' '.__DIR__.'/setup.php');
//    }
//
//    public function tearDown()
//    {
//        $pidFile = sys_get_temp_dir().'/lumen-swoole.pid';
//        $pid = file_get_contents($pidFile);
//
//        posix_kill($pid, SIGTERM);
//
//        usleep(500);
//
//        posix_kill($pid, SIGKILL);
//
//        unlink($pidFile);
//    }

    protected function baseUrl($path)
    {
        return $this->baseUrl.trim($path, '/');
    }
}
