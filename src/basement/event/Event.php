<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2016-12-19 22:10:50
 * @Modified time:      2019-01-06 13:54:59
 * @Depends on Linker:  Config Exception
 * @Description:        事件监听处理
 */
namespace lin\basement\event;

use Linker;
use lin\basement\event\structure\Debug;

class Event
{
    private static $config;

    /*****basement*****/
    use \basement\Event;
    //触发事件
    public static function trigger(string $event, ...$params)
    {
        if (!isset(self::$data[$event])) {
            return null;
        }
        $t = microtime(true);
        $r = call_user_func_array(self::$data[$event]['C'], $params);
        self::$data[$event]['t']--; //减少事件执行次数,不影响t=0的无限执行

        if (self::$data[$event]['t'] === 0) {
            unset(self::$data[$event]);
        }

        if (self::$config['debug']) {
            Debug::trigger($event, $params, microtime(true) - $t);
        }
        return $r;
    }
    /**
     * 绑定事件
     * @param  string   $event    事件名
     * @param  callable $callable 事件回调
     * @param  int      $times    事件可执行次数
     */
    public static function on(string $event, callable $callable, int $times = -1): bool
    {
        $times == 0 and $times = -1;
        self::$data[$event]    = ['C' => $callable, 't' => $times];
        return true;
    }
    //解绑事件
    public static function off(string $event): bool
    {
        unset(self::$data[$event]);
        return true;
    }
    public static function exists(string $event): bool
    {
        return array_key_exists($event, self::$data);
    }
    /******************/

    protected static $data = []; //注册的事件列表
    private static $status = 0; //初始化标记

    //清除所有事件
    public static function clean(string $event = ''): bool
    {
        if ($event) {
            unset(self::$data[$event]);
        } else {
            self::$data = [];
        }
        return true;
    }

    //绑定一次性事件
    public static function one(string $event, callable $callable): bool
    {
        self::on($event, $callable, 1);
        return true;
    }

    //启动事件
    public static function run( ? string $files = '*') : bool
    {
        if (self::$status || $files === null) {
            return false; //初始化启动
        }
        self::$status = 1;
        $t            = microtime(true);
        self::$config = Linker::Config()::get('lin')['event'];
        $path         = rtrim(self::$config['path'], '/') . '/';
        $lists        = explode(',', $files);
        $final_files  = [];
        //获得目标规则文件
        foreach ($lists as $file) {
            $file = trim(ltrim($file, '/'));
            $end  = substr($file, -1);
            if (preg_match('/^[a-zA-Z0-9_\-]$/', $end)) {
                $file .= '.php'; //匹配php文件
            }
            $file = $path . $file;
            $file = glob($file);
            while ($file) {
                $current = array_pop($file);
                if (is_dir($current)) {
                    $file = array_merge($file, glob("$current/*")); //扫描目录
                } else {
                    $final_files[] = $current;
                }
            }
        }

        if (!$final_files) {
            Linker::Exception()::throw ('缺少可用文件', 1, 'Event', $files);
        }

        //加载规则文件
        $time  = [];
        $debug = self::$config['debug'];
        foreach ($final_files as $file) {
            include $file;
            if ($debug) {
                $t1     = microtime(true);
                $time[] = $t1 - $t;
                $t      = $t1;
            }
        }

        if ($debug) {
            Debug::run($final_files, count(self::$data), $time);
        }

        return true;
    }

    public static function reset(): bool
    {
        self::$data   = [];
        self::$status = 0;
        return true;
    }
}
