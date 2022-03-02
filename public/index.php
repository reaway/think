<?php

// [ 应用入口文件 ]
namespace think;

use Think\Component\Container\Container;

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

// 获取容器实例
$container = Container::getInstance();
$container->instance(Container::class, $container);

$container->bind('app', 'think\App');
$app = $container->make('app');

// 执行HTTP应用并响应
$container->bind('http', 'think\Http');
$http = $container->make('http');
$response = $http->run();
//$response->send();
//$http->end($response);
dump(container());
