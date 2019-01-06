<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2017-06-20 11:53:48
 * @Modified time:      2019-01-06 12:45:07
 * @Depends on Linker:  None
 * @Description:        视图类
 */
namespace lin\view;

use Linker;
use lin\view\structure\Parser;

class View
{
    private $data = [];
    private $Parser;

    public function __construct()
    {
        $this->Parser = new Parser($this);
    }

    //批量分配模板变量
    public function withData(array $data): object
    {
        $this->data = array_merge($this->data, $data);
        return $this;
    }

    /**
     * 以单次方式分配方式，分配模板变量
     * @param  string $name  欲分配的变量名
     * @param  mixed  $value 变量值
     * @return object $this
     */
    public function assign(string $name, $value): object
    {
        $this->data[$name] = $value;
        return $this;
    }

    //显示视图页面
    public function show(string $view): void
    {
        $this->Parser->show($view, $this->data); //获得解析后值，末尾加入随机字符
        $this->data = [];
    }
    //获得解析内容
    public function getContents(string $view): string
    {
        $file       = $this->Parser->getParsedFile($view, $this->data); //获得解析后值，末尾加入随机字符
        $this->data = [];
        return file_get_contents($file);
    }
    //获取分配变量
    public function getData(): array
    {
        return $this->data;
    }

    //清除指定缓存
    public static function clearCache(string $files = '*'): bool
    {
        $path        = Linker::Config()::get('lin')['view']['path'];
        $path        = rtrim($path, '/') . '/';
        $files       = explode(',', $files);
        $final_files = [];

        //获得目标规则文件
        foreach ($files as $file) {
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

        //获得最终目标文件
        $Parser = new Parser;
        foreach ($final_files as $file) {
            $file = explode($path, $file)[1]; //去掉路径前缀和文件后缀名
            $file = explode('.php', $file)[0];
            $file = $Parser->getCacheName($file); //获得缓存文件名
            if (file_exists($file)) {
                unlink($file);
            }
        }
        return true;
    }
}
