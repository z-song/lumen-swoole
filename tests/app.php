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

$app->get('test1', function () {
    return 'hello world';
});

$app->get('test2', function (\Illuminate\Http\Request $request) {
    return $request->all();
});

$app->get('test3', function (\Illuminate\Http\Request $request) {
    return $request->cookies->all();
});

$app->post('test4', function (\Illuminate\Http\Request $request) {
    return $request->all();
});

$app->post('test5', function (\Illuminate\Http\Request $request) {
    return $request->cookies->all();
});

$app->get('test6', function (\Illuminate\Http\Request $request) {
    return $request->header('foo');
});

$app->get('test7', function (\Illuminate\Http\Request $request) {
    return [$request->getUser(), $request->getPassword()];
});

$app->post('upload', function (\Illuminate\Http\Request $request) {
    $file = $request->file('file');

    return [$file->getSize(), $file->getClientOriginalName()];
});

$app->get('header', function (\Illuminate\Http\Request $request) {
    return response('', 200, ['foo' => 'hello world']);
});

$app->get('cookie', function (\Illuminate\Http\Request $request) {
    $response = new Illuminate\Http\Response('Hello World');

    $response->withCookie(new \Symfony\Component\HttpFoundation\Cookie('name', 'hello world', 10));

    return $response;
});

$app->get('singleton', function () {
    app()->singleton('hello', function () {
        return 'world';
    });

    return app('hello');
});

$app->get('singleton1', function () {
    return json_encode(app()->bound('hello'));
});

return $app;
