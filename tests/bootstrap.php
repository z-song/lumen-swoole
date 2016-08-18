<?php

$appPath = __DIR__.'/app.php';

exec(__DIR__."/../bin/lumen-swoole -s $appPath -d");

register_shutdown_function(function () use ($appPath) {
    $app = require $appPath;
    $pidFile = $app->storagePath('lumen-swoole.pid');

    if (!file_exists($pidFile)) {
        return;
    }

    $pid = file_get_contents($pidFile);

    posix_kill($pid, SIGTERM);
    usleep(500);
    posix_kill($pid, SIGKILL);
    unlink($pidFile);
});
