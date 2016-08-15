<?php

exec(PHP_BINARY.' '.__DIR__.'/setup.php', $output);

$pidFile = sys_get_temp_dir().'/lumen-swoole.pid';
$pid = file_get_contents($pidFile);

register_shutdown_function(function() use ($pid, $pidFile) {
    //echo sprintf('%s - Killing process with ID %d', date('r'), $pid) . PHP_EOL;

    posix_kill($pid, SIGTERM);
    usleep(500);
    posix_kill($pid, SIGKILL);
    unlink($pidFile);
});