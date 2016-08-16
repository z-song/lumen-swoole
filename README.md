# lumen-swoole

[![Build Status](https://travis-ci.org/z-song/lumen-swoole.svg?branch=master)](https://travis-ci.org/z-song/lumen-swoole)
[![StyleCI](https://styleci.io/repos/65545581/shield)](https://styleci.io/repos/65545581)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/z-song/lumen-swoole/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/z-song/lumen-swoole/?branch=master)

Run lumen on swoole.

## Installation

```
composer require encore/lumen-swoole dev-master
```

## Usage

```
// run server on default host(localhost) and default port(8083).
vendor/bin/lumen-swoole

// run server in daemon mode.
vendor/bin/lumen-swoole -d

// specify host and port.
vendor/bin/lumen-swoole --host=127.0.0.1 --port=88

// stop server
vendor/bin/lumen-swoole stop

// restart server
vendor/bin/lumen-swoole restart

// reload server
vendor/bin/lumen-swoole reload

// run with other options. (see http://wiki.swoole.com/wiki/page/274.html)
vendor/bin/lumen-swoole --worker_num=4 --backlog=128 --max_request=100 --dispatch_mode=1
```

After start server, open http://localhost:8083 in browser.

## Benchmark

```
➜  lumen-v5.2.1 git:(master) ab -c 200 -n 200000 -k http://127.0.0.1:8083/
This is ApacheBench, Version 2.3 <$Revision: 1706008 $>
Copyright 1996 Adam Twiss, Zeus Technology Ltd, http://www.zeustech.net/
Licensed to The Apache Software Foundation, http://www.apache.org/

Benchmarking 127.0.0.1 (be patient)
Completed 20000 requests
Completed 40000 requests
Completed 60000 requests
Completed 80000 requests
Completed 100000 requests
Completed 120000 requests
Completed 140000 requests
Completed 160000 requests
Completed 180000 requests
Completed 200000 requests
Finished 200000 requests


Server Software:        swoole-http-server
Server Hostname:        127.0.0.1
Server Port:            8083

Document Path:          /
Document Length:        40 bytes

Concurrency Level:      200
Time taken for tests:   33.035 seconds
Complete requests:      200000
Failed requests:        0
Keep-Alive requests:    200000
Total transferred:      43600000 bytes
HTML transferred:       8000000 bytes
Requests per second:    6054.18 [#/sec] (mean)
Time per request:       33.035 [ms] (mean)
Time per request:       0.165 [ms] (mean, across all concurrent requests)
Transfer rate:          1288.88 [Kbytes/sec] received

Connection Times (ms)
              min  mean[+/-sd] median   max
Connect:        0    0   0.2      0       9
Processing:    17   33   6.2     31      81
Waiting:       17   33   6.2     31      81
Total:         17   33   6.2     31      81

Percentage of the requests served within a certain time (ms)
  50%     31
  66%     33
  75%     35
  80%     36
  90%     41
  95%     46
  98%     52
  99%     56
 100%     81 (longest request)

```

## License

MIT