<?php

/**
 * 测试启动
 */

error_reporting(E_ALL);
define('__ROOT__', dirname(__DIR__));
define('__TMP__', __ROOT__ . '/tests/tmp');
define('__DB__', __ROOT__ . '/tests/datasets');
define('__TEST__', __ROOT__ . '/tests');
$vendor  = __ROOT__ . '/vendor';
$vendor2 = realpath(__ROOT__ . '/../../');

//引入basement启动
if (file_exists($vendor)) {
} else if (file_exists($vendor2)) {
    $vendor = $vendor2;
} else {
    throw new Exception("can not find autoload.php file", 1);
}
require $vendor . '/basement/basement/boot.php';

//注册组件
Linker::register([
    'Config'  => '\\lin\\basement\\config\\Config',
    'Request' => '\\lin\\basement\\request\\Request',
], true);

Linker::register([
    'ServerSQL'   => '\\lin\\basement\\server\\sql\\SQLPDO',
    'ServerKV'    => '\\lin\\basement\\server\\kv\\KVLocal',
    'ServerLocal' => '\\lin\\basement\\server\\local\\Local',
    'ServerQueue' => '\\lin\\basement\\server\\queue\\LocalQueue',
    'Log'         => '\\lin\\basement\\log\\log',

    'Exception'   => '\\lin\\basement\\exception\\GeneralException',
    'Debug'       => '\\lin\\basement\\debug\\Debug',
    'Event'       => '\\lin\\basement\\event\\Event',
    'Lang'        => '\\lin\\basement\\lang\\Lang',

]);

//引入composer自动加载
require $vendor . '/autoload.php';

//读取配置
Linker::Config()::set('lin', include __ROOT__ . '/config/test-lin.php');
Linker::Config()::set('servers', include __ROOT__ . '/config/test-servers.php');

if (!file_exists(__TMP__)) {
    mkdir(__TMP__, 0750, true);
}
