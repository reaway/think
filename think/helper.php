<?php

use think\App;

if (!function_exists('app')) {
    /**
     * 获取当前Request对象实例
     * @return App
     */
    function app(): App
    {
        return container('app');
    }
}