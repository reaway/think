<?php

use Think\Component\Env\Env;
use Think\Component\Config\Config;
use Think\Component\Event\Event;
use Think\Component\Lang\Lang;
use Think\Component\Request\Request;
use Think\Component\Response\Response;
use Think\Component\Middleware\Middleware;
use Think\Component\Route\Route;
use Think\Component\Log\Log;

// 容器Provider定义文件
return [
    'env' => Env::class,
    'config' => Config::class,
    'event' => Event::class,
    'lang' => Lang::class,
    'request' => Request::class,
    'response' => Response::class,
    'middleware' => Middleware::class,
    'route' => Route::class,
    'log' => Log::class,
];