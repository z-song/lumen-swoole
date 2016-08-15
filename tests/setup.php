<?php

require __DIR__.'/../vendor/autoload.php';

try {
    (new Dotenv\Dotenv(__DIR__.'/../'))->load();
} catch (Dotenv\Exception\InvalidPathException $e) {
    //
}

$app = new Laravel\Lumen\Application(
    realpath(__DIR__.'/../vendor/laravel/lumen')
);

// $app->withFacades();

// $app->withEloquent();

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);


// $app->middleware([
//    App\Http\Middleware\ExampleMiddleware::class
// ]);

// $app->routeMiddleware([
//     'auth' => App\Http\Middleware\Authenticate::class,
// ]);


// $app->register(App\Providers\AppServiceProvider::class);
// $app->register(App\Providers\AuthServiceProvider::class);
// $app->register(App\Providers\EventServiceProvider::class);

//$app->group(['namespace' => 'App\Http\Controllers'], function ($app) {
//    require __DIR__.'/../app/Http/routes.php';
//});

$app->get('/', function () use ($app) {
    return $app->version();
});

$app->get('/test', function () {
    return 'hello world';
});

$server = new \Encore\LumenSwoole\Server($app);

$server->options(['daemonize' => true])->run();
