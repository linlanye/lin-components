<?php
// 可作为单独组件使用的启动文件，生产环境用，默认basement组件已注册好！
if (!Linker::Config()::exists('lin')) {
    Linker::Config()::set('lin', include __DIR__ . '/config/lin.production.php');
}
if (!Linker::Config()::exists('servers')) {
    Linker::Config()::set('servers', include __DIR__ . '/config/lin-servers.php');
}
