<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2018-05-31 14:04:33
 * @Modified time:      2018-12-10 20:33:26
 * @Depends on Linker:  Config
 * @Description:        使用控制台测试，默认请求方法为post
 */
namespace lin\tests\basement;

use Linker;
use lin\basement\request\Request;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{
    private $config;

    protected function setUp()
    {
        $this->config = Linker::Config()::lin('request');
    }
    protected function tearDown()
    {
        unset($_SERVER['REQUEST_URI']);
        unset($_SERVER['REQUEST_METHOD']);
        unset($_SERVER['HTTP_HOST']);
        unset($_SERVER['SERVER_PORT']);

        Request::reset();
    }

    public function testGetSet()
    {
        $_SERVER['REQUEST_URI']    = '/';
        $_SERVER['REQUEST_METHOD'] = 'POST';

        //断言设置指定方法
        $data = [
            md5(mt_rand()) => md5(mt_rand()),
        ];
        $method = md5(mt_rand());
        $this->assertNull(Request::get($method)); //不存在
        Request::set($method, $data);
        $this->assertSame(Request::get($method), $data);

        //断言当前方法
        $data = [
            md5(mt_rand()) => md5(mt_rand()),
        ];
        $this->assertEmpty(Request::getCurrent()); //Reqeust已初始化
        Request::setCurrent($data);
        $this->assertSame(Request::getCurrent(), $data);
        $this->assertSame(Request::getCurrent(), $_POST);

    }
    public function testUserMethod()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        //使用post做模拟
        $_POST              = [md5(mt_rand()) => md5(mt_rand())];
        $userMethod         = md5(mt_rand());
        $methodName         = $this->config['type']['tag'];
        $_POST[$methodName] = $userMethod;

        //测试模拟方法
        $this->assertNotSame(Request::getMethod(), Request::getRawMethod());
        $this->assertSame(Request::getRawMethod(), 'POST');
        $this->assertSame(Request::getMethod(), strtoupper($userMethod));

        //测试用户模拟的自定义请求是否释放原始请求参数
        $this->assertTrue(empty(Request::get('post')));
        $this->assertTrue(empty($_POST));
    }

    public function testMethod()
    {
        $this->assertNull(Request::getMethod());
        Request::reset();

        //模拟方法
        $method                    = md5(mt_rand());
        $_SERVER['REQUEST_METHOD'] = $method;

        $this->assertSame(Request::getMethod(), Request::getRawMethod());
        $this->assertSame(Request::getMethod(), strtoupper($method));
    }
    public function testDynamicGetSet()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $key     = md5(mt_rand());
        $value   = md5(mt_rand());
        $_POST   = [$key => $value];
        $Request = new Request;

        //测试读取
        $this->assertSame($Request->$key, Request::getCurrent()[$key]);
        $this->assertTrue(isset($Request->$key));
        $this->assertFalse(isset($Request->none));

        //测试写
        $value         = md5(mt_rand());
        $Request->$key = $value;
        $this->assertSame($Request->$key, Request::getCurrent()[$key]);
    }

    //测试动态读写参数
    public function testParams()
    {
        $method  = md5(mt_rand());
        $k0      = md5(mt_rand());
        $k1      = md5(mt_rand());
        $k2      = md5(mt_rand());
        $v       = md5(mt_rand());
        $Request = new Request;
        $Request->params(["$method.$k0.$k1.$k2" => $v]);
        $this->assertSame($Request->params("$method.$k0.$k1.$k2"), $v);
        $this->assertSame($Request->params($method), [$k0 => [$k1 => [$k2 => $v]]]);
        $all = [strtoupper($method) => [$k0 => [$k1 => [$k2 => $v]]]];
        $this->assertSame($Request->params(), $all);
        $this->assertSame($Request->get($method), current($all));

        //特殊情况
        $all = [strtoupper($method) => [$v]];
        $Request->params([$method => $v]);
        $this->assertSame($Request->get($method), current($all));
        $this->assertSame($Request->params(), $all);

        $Request->params(["$method." => $v]);
        $this->assertSame($Request->get($method), [$v]);
        $this->assertSame($Request->params(), $all); //获取所有
        $this->assertSame($Request->params('*'), $all); //获取所有
        $this->assertSame($Request->params(''), null); //什么都不获取

    }

    public function testGetURL()
    {
        //CLI下无法获取
        $this->assertNull(Request::getURL());

        //模拟
        $url                    = md5(mt_rand()) . '/' . md5(mt_rand());
        $_SERVER['REQUEST_URI'] = $url;
        $this->assertSame(Request::getURL(), '/' . $url);
    }

    public function testGetHostPort()
    {
        //CLI下无法获取
        $this->assertNull(Request::getHost());
        $this->assertNull(Request::getPort());

        //模拟
        $host                   = md5(mt_rand());
        $port                   = mt_rand();
        $_SERVER['HTTP_HOST']   = $host;
        $_SERVER['SERVER_PORT'] = $port;
        $this->assertSame(Request::getHost(), $host);
        $this->assertSame(Request::getPort(), $port);
    }

    public function testUploads()
    {
        //模拟数据
        $_FILES = [
            'item1' => [
                'name'     => [
                    'some file.ext',
                    'another file.ext',
                ],
                'type'     => [
                    'some type',
                    'another type',
                ],
                'tmp_name' => [
                    'tmp_directory',
                    'tmp_directory',
                ],
                'error'    => [
                    Request::UPLOAD_OK,
                    Request::UPLOAD_INI_SIZE,
                ],
                'size'     => [
                    1000,
                    2000,
                ],
            ],
            'item2' => [
                'name'     => 'some file2.ext',
                'type'     => 'some type2',
                'tmp_name' => 'tmp_directory',
                'error'    => Request::UPLOAD_FORM_SIZE,
                'size'     => 3000,
            ],
        ];

        $Request = new Request;
        $r       = Request::getUploadsError();
        $this->assertSame($r['item1'][0], Request::UPLOAD_NOT_UPLOADED_FILE);
        $this->assertSame($r['item1'][1], Request::UPLOAD_INI_SIZE);
        $this->assertSame($r['item2'][0], Request::UPLOAD_FORM_SIZE);
        $this->assertTrue(empty(Request::getUploads()));

        //复原
        $_FILES = [];
        @rmdir($this->config['uploads']['path']);
    }

}
