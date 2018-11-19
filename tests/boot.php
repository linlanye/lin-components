<?php

error_reporting(E_ALL);

//引入composer
define('__VENDOR__', realpath(dirname(__FILE__)));
require __VENDOR__ . '/autoload.php';

define('__ROOT__', __VENDOR__ . '/lin/components');
define('__TMP__', __ROOT__ . '/tests/tmp');
define('__DB__', __ROOT__ . '/tests/datasets');
define('__TEST__', __ROOT__ . '/tests');

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

//读取配置

Linker::Config()::set('lin', include __ROOT__ . '/config/test-lin.php');
Linker::Config()::set('servers', include __ROOT__ . '/config/test-servers.php');

if (!file_exists(__TMP__)) {
    mkdir(__TMP__, 0750, true);
}
