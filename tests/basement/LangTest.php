<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2018-06-11 15:47:02
 * @Modified time:      2018-12-07 14:19:24
 * @Depends on Linker:  Config
 * @Description:        测试语言映射
 */
namespace lin\tests\basement;

use Linker;
use lin\basement\lang\Lang;
use PHPUnit\Framework\TestCase;

class LangTest extends TestCase
{
    public static function tearDownAfterClass()
    {
        Lang::reset();
    }
    public function testName()
    {
        //实例化时候设置标签名
        $name = md5(mt_rand());
        $Lang = new Lang($name);
        $this->assertSame($name, $Lang->getName());

        //设置标签名
        $name = md5(mt_rand());
        $Lang->setName($name);
        $this->assertSame($name, $Lang->getName());

        //默认标签
        $Lang = new Lang;
        $this->assertSame(Linker::Config()::lin('lang.default.name'), $Lang->getName());
    }
    public function testI18N()
    {
        $this->assertTrue(Lang::i18n('en'));
        $this->assertFalse(Lang::i18n('none'));
    }
    public function testDefaultMap()
    {
        //断言按配置文件走
        Lang::i18n('en');
        $Lang = new Lang('lin');
        $this->assertSame('failed to open file', $Lang->map('文件打开失败'));
    }
    public function testMap()
    {
        //自定义加载规则
        $origin = md5(mt_rand());
        $name   = md5(mt_rand());
        $i18n   = 'en';
        Lang::i18n($i18n);
        Lang::autoload(function ($name, $i18n) use ($origin) {
            return [$origin => "$name$i18n"];
        });
        $Lang = new Lang($name);

        //断言找到映射
        $this->assertSame($Lang->map($origin), $name . $i18n);
        //断言未找到映射
        $origin2 = md5(mt_rand());
        $this->assertSame($Lang->map($origin2), $origin2);

        //断言未找到映射按设置返回
        $fiexed = md5(mt_rand()); //固定返回
        Linker::Config()::lin(['lang.default.map' => function ($chars) use ($fiexed) {
            return $fiexed;
        }]);
        $Lang = new Lang($name);
        $this->assertSame($fiexed, $Lang->map(mt_rand()));
        $this->assertSame($fiexed, $Lang->map(mt_rand()));
    }

}
