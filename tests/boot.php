<?php

//定义常量
error_reporting(E_ALL);
define('__ROOT__', dirname(__DIR__));
define('__TMP__', __ROOT__ . '/tests/tmp');
define('__DB__', __ROOT__ . '/tests/datasets');
define('__TEST__', __ROOT__ . '/tests');
$autoload  = realpath(__ROOT__ . '/../../') . '/autoload.php'; //优先使用最上层
$autoload2 = __ROOT__ . '/vendor/autoload.php';

if (file_exists($autoload)) {
    require $autoload;
} else if (file_exists($autoload2)) {
    require $autoload2;
} else {
    throw new Exception("can not find autoload.php file");
}

//注册组件
Linker::register([
    'ServerSQL'   => 'lin\\basement\\server\\sql\\SQLPDO',
    'ServerKV'    => 'lin\\basement\\server\\kv\\KVLocal',
    'ServerLocal' => 'lin\\basement\\server\\local\\Local',
    'ServerQueue' => 'lin\\basement\\server\\queue\\LocalQueue',
    'Log'         => 'lin\\basement\\log\\log',
    'Exception'   => 'lin\\basement\\exception\\GeneralException',
    'Debug'       => 'lin\\basement\\debug\\Debug',
    'Event'       => 'lin\\basement\\event\\Event',
    'Lang'        => 'lin\\basement\\lang\\Lang',
    'Config'      => 'lin\\basement\\config\\Config',
    'Request'     => 'lin\\basement\\request\\Request',
]);

$autoload = new Composer\Autoload\ClassLoader;
$autoload->addPsr4('lin\\tests\\', __DIR__);
$autoload->register();

//读取配置
Linker::Config()::set('lin', include __ROOT__ . '/config/test-lin.php');
Linker::Config()::set('servers', include __ROOT__ . '/config/test-servers.php');

if (!file_exists(__TMP__)) {
    mkdir(__TMP__, 0750, true); //创建临时文件夹
}
