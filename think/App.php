<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2021 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
declare (strict_types = 1);

namespace think;

use think\event\AppInit;
use think\helper\Str;
use think\initializer\BootService;
use think\initializer\Error;
use think\initializer\RegisterService;
use Think\Component\Container\Container;
use Think\Component\Env\Env;
use Think\Component\Config\Config;
use Think\Component\Event\Event;
use Think\Component\Lang\Lang;

/**
 * App 基础类
 * @property Container  $container
 * @property Env        $env
 * @property Config     $config
 * @property Event      $event
 * @property Lang       $lang
 */
class App
{
    const VERSION = '6.0.12LTS';

    /**
     * 应用调试模式
     * @var bool
     */
    protected $appDebug = false;

    /**
     * 环境变量标识
     * @var string
     */
    protected $envName = '';

    /**
     * 应用开始时间
     * @var float
     */
    protected $beginTime;

    /**
     * 应用内存初始占用
     * @var integer
     */
    protected $beginMem;

    /**
     * 当前应用类库命名空间
     * @var string
     */
    protected $namespace = 'app';

    /**
     * 应用根目录
     * @var string
     */
    protected $rootPath = '';

    /**
     * 框架目录
     * @var string
     */
    protected $thinkPath = '';

    /**
     * 应用目录
     * @var string
     */
    protected $appPath = '';

    /**
     * Runtime目录
     * @var string
     */
    protected $runtimePath = '';

    /**
     * 路由定义目录
     * @var string
     */
    protected $routePath = '';

    /**
     * 配置后缀
     * @var string
     */
    protected $configExt = '.php';

    /**
     * 应用初始化器
     * @var array
     */
    protected $initializers = [
        Error::class,
        RegisterService::class,
        BootService::class,
    ];

    /**
     * 注册的系统服务
     * @var array
     */
    protected $services = [];

    /**
     * 初始化
     * @var bool
     */
    protected $initialized = false;

    /**
     * @var Container
     */
    private $container;

    /**
     * @var Env
     */
    private $env;

    /**
     * 架构方法
     * @access public
     * @param string $rootPath 应用根目录
     */
    public function __construct(string $rootPath = '')
    {
        $this->thinkPath   = __DIR__ . DIRECTORY_SEPARATOR;
        $this->rootPath    = $rootPath ? rtrim($rootPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR : $this->getDefaultRootPath();
        $this->appPath     = $this->rootPath . 'app' . DIRECTORY_SEPARATOR;
        $this->runtimePath = $this->rootPath . 'runtime' . DIRECTORY_SEPARATOR;

        $this->container = Container::getInstance();

        if (is_file($this->appPath . 'provider.php')) {
            $this->container->bind(include $this->appPath . 'provider.php');
        }
        $this->env = $this->container->make('env');
        $this->config = $this->container->make('config');
        $this->event = $this->container->make('event');
        $this->lang = $this->container->make('lang');

        $this->container->instance('app', $this);
        $this->container->instance('think\App', $this);
    }

    /**
     * 注册服务
     * @access public
     * @param Service|string $service 服务
     * @param bool           $force   强制重新注册
     * @return Service|null
     */
    public function register($service, bool $force = false)
    {
        $registered = $this->getService($service);

        if ($registered && !$force) {
            return $registered;
        }

        if (is_string($service)) {
            $service = new $service($this);
        }

        if (method_exists($service, 'register')) {
            $service->register();
        }

        if (property_exists($service, 'bind')) {
            $this->bind($service->bind);
        }

        $this->services[] = $service;
    }

    /**
     * 执行服务
     * @access public
     * @param Service $service 服务
     * @return mixed
     */
    public function bootService($service)
    {
        if (method_exists($service, 'boot')) {
            return $this->invoke([$service, 'boot']);
        }
    }

    /**
     * 获取服务
     * @param string|Service $service
     * @return Service|null
     */
    public function getService($service)
    {
        $name = is_string($service) ? $service : get_class($service);
        return array_values(array_filter($this->services, function ($value) use ($name) {
            return $value instanceof $name;
        }, ARRAY_FILTER_USE_BOTH))[0] ?? null;
    }

    /**
     * 开启应用调试模式
     * @access public
     * @param bool $debug 开启应用调试模式
     * @return $this
     */
    public function debug(bool $debug = true)
    {
        $this->appDebug = $debug;
        return $this;
    }

    /**
     * 是否为调试模式
     * @access public
     * @return bool
     */
    public function isDebug(): bool
    {
        return $this->appDebug;
    }

    /**
     * 设置应用命名空间
     * @access public
     * @param string $namespace 应用命名空间
     * @return $this
     */
    public function setNamespace(string $namespace)
    {
        $this->namespace = $namespace;
        return $this;
    }

    /**
     * 获取应用类库命名空间
     * @access public
     * @return string
     */
    public function getNamespace(): string
    {
        return $this->namespace;
    }

    /**
     * 设置环境变量标识
     * @access public
     * @param string $name 环境标识
     * @return $this
     */
    public function setEnvName(string $name)
    {
        $this->envName = $name;
        return $this;
    }

    /**
     * 获取框架版本
     * @access public
     * @return string
     */
    public function version(): string
    {
        return static::VERSION;
    }

    /**
     * 获取应用根目录
     * @access public
     * @return string
     */
    public function getRootPath(): string
    {
        return $this->rootPath;
    }

    /**
     * 获取应用基础目录
     * @access public
     * @return string
     */
    public function getBasePath(): string
    {
        return $this->rootPath . 'app' . DIRECTORY_SEPARATOR;
    }

    /**
     * 获取当前应用目录
     * @access public
     * @return string
     */
    public function getAppPath(): string
    {
        return $this->appPath;
    }

    /**
     * 设置应用目录
     * @param string $path 应用目录
     */
    public function setAppPath(string $path)
    {
        $this->appPath = $path;
    }

    /**
     * 获取应用运行时目录
     * @access public
     * @return string
     */
    public function getRuntimePath(): string
    {
        return $this->runtimePath;
    }

    /**
     * 设置runtime目录
     * @param string $path 定义目录
     */
    public function setRuntimePath(string $path): void
    {
        $this->runtimePath = $path;
    }

    /**
     * 获取核心框架目录
     * @access public
     * @return string
     */
    public function getThinkPath(): string
    {
        return $this->thinkPath;
    }

    /**
     * 获取应用配置目录
     * @access public
     * @return string
     */
    public function getConfigPath(): string
    {
        return $this->rootPath . 'config' . DIRECTORY_SEPARATOR;
    }

    /**
     * 获取配置后缀
     * @access public
     * @return string
     */
    public function getConfigExt(): string
    {
        return $this->configExt;
    }

    /**
     * 获取应用开启时间
     * @access public
     * @return float
     */
    public function getBeginTime(): float
    {
        return $this->beginTime;
    }

    /**
     * 获取应用初始内存占用
     * @access public
     * @return integer
     */
    public function getBeginMem(): int
    {
        return $this->beginMem;
    }

    /**
     * 加载环境变量定义
     * @access public
     * @param string $envName 环境标识
     * @return void
     */
    public function loadEnv(string $envName = ''): void
    {
        // 加载环境变量
        $envFile = $envName ? $this->rootPath . '.env.' . $envName : $this->rootPath . '.env';

        if (is_file($envFile)) {
            $this->env->load($envFile);
        }
    }

    /**
     * 初始化应用
     * @access public
     * @return $this
     */
    public function initialize()
    {
        $this->initialized = true;

        $this->beginTime = microtime(true);
        $this->beginMem  = memory_get_usage();

        $this->loadEnv($this->envName);

        $this->configExt = $this->env->get('config_ext', '.php');

        $this->debugModeInit();

        // 加载全局初始化文件
        $this->load();

        // 加载应用默认语言包
        $this->loadLangPack();

        // 监听AppInit
        $this->event->trigger(AppInit::class);

        date_default_timezone_set($this->config->get('app.default_timezone', 'Asia/Shanghai'));

        // 初始化
        foreach ($this->initializers as $initializer) {
            //$this->container->make($initializer)->init($this);
        }

        return $this;
    }

    /**
     * 是否初始化过
     * @return bool
     */
    public function initialized()
    {
        return $this->initialized;
    }

    /**
     * 加载语言包
     * @return void
     */
    public function loadLangPack()
    {
        // 加载默认语言包
        $langSet = $this->lang->defaultLangSet();
        $this->switchLangSet($langSet);
    }

    /**
     * 切换语言
     * @access public
     * @param string $langset 语言
     * @return void
     */
    public function switchLangSet(string $langset)
    {
        if (empty($langset)) {
            return;
        }

        // 加载系统语言包
        $this->lang->load([
            $this->getThinkPath() . 'lang' . DIRECTORY_SEPARATOR . $langset . '.php',
        ]);

        // 加载系统语言包
        $files = glob($this->getAppPath() . 'lang' . DIRECTORY_SEPARATOR . $langset . '.*');
        $this->lang->load($files);

        // 加载扩展（自定义）语言包
        $list = $this->config->get('lang.extend_list', []);
        if (isset($list[$langset])) {
            $this->lang->load($list[$langset]);
        }
    }

    /**
     * 引导应用
     * @access public
     * @return void
     */
    public function boot(): void
    {
        array_walk($this->services, function ($service) {
            $this->bootService($service);
        });
    }

    /**
     * 加载应用文件和配置
     * @access protected
     * @return void
     */
    protected function load(): void
    {
        $appPath = $this->getAppPath();

        if (is_file($appPath . 'common.php')) {
            include_once $appPath . 'common.php';
        }

        include_once $this->thinkPath . 'helper.php';

        $configPath = $this->getConfigPath();
        $files = [];
        if (is_dir($configPath)) {
            $files = glob($configPath . '*' . $this->configExt);
        }
        foreach ($files as $file) {
            $this->config->load($file, pathinfo($file, PATHINFO_FILENAME));
        }

        if (is_file($appPath . 'event.php')) {
            $this->loadEvent(include $appPath . 'event.php');
        }

        if (is_file($appPath . 'service.php')) {
            $services = include $appPath . 'service.php';
            foreach ($services as $service) {
                $this->register($service);
            }
        }
    }

    /**
     * 调试模式设置
     * @access protected
     * @return void
     */
    protected function debugModeInit(): void
    {
        // 应用调试模式
        if (!$this->appDebug) {
            $this->appDebug = $this->env->get('app_debug') ? true : false;
            ini_set('display_errors', 'Off');
        }

        if (!$this->runningInConsole()) {
            //重新申请一块比较大的buffer
            if (ob_get_level() > 0) {
                $output = ob_get_clean();
            }
            ob_start();
            if (!empty($output)) {
                echo $output;
            }
        }
    }

    /**
     * 注册应用事件
     * @access protected
     * @param array $event 事件数据
     * @return void
     */
    public function loadEvent(array $event): void
    {
        if (isset($event['bind'])) {
            $this->event->bind($event['bind']);
        }

        if (isset($event['listen'])) {
            $this->event->listenEvents($event['listen']);
        }

        if (isset($event['subscribe'])) {
            $this->event->subscribe($event['subscribe']);
        }
    }

    /**
     * 解析应用类的类名
     * @access public
     * @param string $layer 层名 controller model ...
     * @param string $name  类名
     * @return string
     */
    public function parseClass(string $layer, string $name): string
    {
        $name  = str_replace(['/', '.'], '\\', $name);
        $array = explode('\\', $name);
        $class = Str::studly(array_pop($array));
        $path  = $array ? implode('\\', $array) . '\\' : '';

        return $this->namespace . '\\' . $layer . '\\' . $path . $class;
    }

    /**
     * 是否运行在命令行下
     * @return bool
     */
    public function runningInConsole(): bool
    {
        return php_sapi_name() === 'cli' || php_sapi_name() === 'phpdbg';
    }

    /**
     * 获取应用根目录
     * @access protected
     * @return string
     */
    protected function getDefaultRootPath(): string
    {
        return dirname(__DIR__) . DIRECTORY_SEPARATOR;
    }

}
