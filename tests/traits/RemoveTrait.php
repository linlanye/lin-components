<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2018-07-10 11:07:13
 * @Modified time:      2019-01-02 22:40:46
 * @Depends on Linker:  None
 * @Description:        对文件夹进行删除
 */
namespace lin\tests\traits;

trait RemoveTrait
{
    protected static function rmdir($dir)
    {
        $dir = trim($dir, '/') . '/';
        if (!is_dir($dir)) {
            unlink($dir);
            return;
        }
        foreach (scandir($dir) as $file) {
            if ($file != '.' && $file != '..') {
                if (is_dir($dir . $file)) {
                    self::rmdir($dir . $file);
                } else {
                    unlink($dir . $file);
                }
            }
        }
        rmdir($dir);
    }
}
