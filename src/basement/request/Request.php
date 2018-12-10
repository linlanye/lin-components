<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2017-06-20 11:53:48
 * @Modified time:      2018-12-10 20:34:57
 * @Depends on Linker:  Config Exception
 * @Description:        HTTP请求类，提供请求相关的一系列操作
 */
namespace lin\basement\request;

use Linker;

class Request
{
    /*****basement*****/
    use \basement\Request;

    public static function get(string $method):  ? array
    {
        self::init();
        $method = strtoupper($method);
        return self::$data[$method] ?? null;
    }

    public static function getCurrent() :  ? array
    {
        self::init();
        if (!self::$method || !isset(self::$data[self::$method])) {
            return null;
        }
        return self::$data[self::$method];
    }

    public static function set(string $method, array $data) : bool
    {
        self::init();
        $method              = strtoupper($method);
        self::$data[$method] = $data;
        return true;
    }

    public static function setCurrent(array $data): bool
    {
        self::init();
        if (self::$method) {
            self::$data[self::$method] = $data;
            return true;
        }
        return false;
    }

    //header头不区分大小写和不区分-和_
    public static function getHeader(string $header):  ? string
    {
        self::init();
        if (function_exists('\getallheaders')) {
            $headers = array_change_key_case(getallheaders(), CASE_UPPER);
            $header  = str_replace('_', '-', $header);
            return $headers[strtoupper($header)] ?? null;
        }

        //通过$_SERVER寻找
        foreach ($_SERVER as $key => $value) {
            if (substr($key, 0, 5) === 'HTTP_') {
                $key    = substr($key, 5); //HTTP开头认为是header头
                $header = str_replace('-', '_', $header); //不区分-和_
                if (strcasecmp($key, $header) == 0) {
                    return $value;
                }
            }
        }
        return null;
    }

    public static function getMethod() :  ? string
    {
        self::init();
        return self::$method;
    }

    public static function getHost() :  ? string
    {
        self::init();
        return $_SERVER['HTTP_HOST'] ?? null;
    }
    public static function getPort() :  ? int
    {
        self::init();
        return $_SERVER['SERVER_PORT'] ?? null;
    }

    //获取请求的相对有效路径
    public static function getURL() :  ? string
    {
        self::init();
        if (!isset($_SERVER['REQUEST_URI'])) {
            return null;
        }
        if (!self::$url) {
            $script    = basename($_SERVER['SCRIPT_NAME']); //获得入口脚本名
            $pos       = strrpos($_SERVER['PHP_SELF'], $script);
            $before    = preg_quote(substr($_SERVER['PHP_SELF'], 0, $pos)); //获得入口脚本名前的url，需转移可能存在的'\'字符
            $url       = preg_replace("#^$before#", '', $_SERVER['REQUEST_URI'], 1); //将完整的url去掉入口文件之前的url，得到相对的url地址，但包含入口文件名
            $url       = '/' . ltrim(preg_replace("#^$script#", '', $url, 1), '/'); //去掉入口脚本名，得到入口文件之后的完整url
            self::$url = explode('?', $url)[0]; //去掉get参数
        }
        return self::$url;
    }
    //获得ip
    public static function getIP() :  ? string
    {
        self::init();
        return $_SERVER['HTTP_X_FORWARDED_FOR'] ?? ($_SERVER['REMOTE_ADDR'] ?? null);
    }
    //获取当前请求协议
    public static function getProtocol() :  ? string
    {
        self::init();
        return $_SERVER['SERVER_PROTOCOL'] ?? null;
    }
    /******************/

    //上传相关常量
    const UPLOAD_OK                = UPLOAD_ERR_OK;
    const UPLOAD_INI_SIZE          = UPLOAD_ERR_INI_SIZE;
    const UPLOAD_FORM_SIZE         = UPLOAD_ERR_FORM_SIZE;
    const UPLOAD_PARTIAL           = UPLOAD_ERR_PARTIAL;
    const UPLOAD_NO_FILE           = UPLOAD_ERR_NO_FILE;
    const UPLOAD_NO_TMP_DIR        = UPLOAD_ERR_NO_TMP_DIR;
    const UPLOAD_CANT_WTITE        = UPLOAD_ERR_CANT_WRITE;
    const UPLOAD_EXTENSION         = UPLOAD_ERR_EXTENSION;
    const UPLOAD_NOT_UPLOADED_FILE = 9; //不是上传文件
    const UPLOAD_INVALID           = 10; //用户自定义校验不过关
    const UPLOAD_CANT_MOVE         = 11; //无法移动文件

    private static $rawMethod; //固有的请求类型
    private static $status = 0; //请求是否已初始化标记
    private static $data   = []; //存放各类请求所携带的数据
    private static $method; //当前请求方法
    private static $uploads = ['ok' => [], 'error' => []]; //上传数据
    private static $url;

    public function __construct()
    {
        self::init();
    }
    //动态获取当前请求类型所携带的某个参数
    public function __get($param)
    {
        return self::$data[self::$method][$param] ?? null;
    }
    //动态设置当前请求类型所携带的某个参数
    public function __set($param, $value)
    {
        self::$data[self::$method][$param] = $value;
    }
    //动态查看当前请求类型是否存在某个参数
    public function __isset($param)
    {
        return isset(self::$data[self::$method][$param]);
    }
    /**
     * 链式调用读写参数
     * @param  string|array $stringOrArray 字符串时为读取，数组为写入，形如['post.id'=>'1'],*返回所有，空返回null
     * @return mixed|null   设置参数时返回true，读取返回具体值或null
     */
    public function params($stringOrArray = '*')
    {
        //设置
        if (is_array($stringOrArray)) {
            foreach ($stringOrArray as $key => $value) {
                $key    = rtrim($key, '.'); //去掉右边.
                $nodes  = explode('.', $key);
                $method = strtoupper($nodes[0]);
                unset($nodes[0]);
                if ($nodes) {
                    $this->setHanlde(self::$data[$method], $nodes, $value);
                } else {
                    if (!is_array($value)) {
                        $value = [$value]; //根节点情况，$params是整个方法携带的参数，value非数组需要为数组形式
                    }
                    self::$data[$method] = $value;
                }
            }
            return true;
        }

        //读取
        if ($stringOrArray == '*') {
            return self::$data; //*返回所有
        }
        if (!$stringOrArray) {
            return null; //空返回null
        }
        $nodes    = explode('.', rtrim($stringOrArray, '.'));
        $nodes[0] = strtoupper($nodes[0]); //第一个元素为方法名
        $ref      = &self::$data;
        foreach ($nodes as $key) {
            if (isset($ref[$key])) {
                $ref = &$ref[$key];
            } else {
                return null;
            }
        }
        return $ref;
    }

    //链式读参数
    private function setHanlde(&$params, $nodes, $value)
    {
        foreach ($nodes as $key) {
            if (!isset($params[$key])) {
                $params[$key] = [];
            }
            $params = &$params[$key];
        }
        $params = $value;
    }

    //清楚某个方法下的数据
    public static function clean(string $method = '') : bool
    {
        if ($method) {
            unset(self::$data[$method]);
        } else {
            self::$data = [];
        }
        return true;
    }
    //清空并重值
    public static function reset(): bool
    {
        self::$status  = 0;
        self::$url     = self::$rawMethod     = self::$method     = null;
        self::$data    = [];
        self::$uploads = ['data' => [], 'error' => []];
        return true;
    }
    //获取上传文件信息
    public static function getUploads():  ? array
    {
        self::init();
        return self::$uploads['ok'] ?: null;
    }

    //获取上传文件错误信息
    public static function getUploadsError(): array
    {
        self::init();
        return self::$uploads['error'];
    }

    //获得原始请求类型
    public static function getRawMethod():  ? string
    {
        self::init();
        return self::$rawMethod;
    }

    //初始化请求
    private static function init()
    {
        if (self::$status) {
            return;
        }
        $config = Linker::Config()::get('lin')['request'];

        //标记运行状态，设置常用的请求及参数
        if (isset($_SERVER['REQUEST_METHOD'])) {
            self::$method = self::$rawMethod = strtoupper($_SERVER['REQUEST_METHOD']);

            //对GET POST方法赋值，
            self::$data['GET'] = &$_GET;
            if (!$_POST) {
                $_POST = file_get_contents('php://input') ?: [];
            }
            self::$data['POST'] = &$_POST;
            if (!isset(self::$data[self::$method])) {
                self::$data[self::$method] = file_get_contents('php://input') ?: []; //其他类型统一读取php输入流
            }
        }

        //处理上传文件
        self::handleUploads($config['uploads']);

        //是否具有自定义请求类型
        $substitute = $config['type']['substitute']; //用于模拟替代的请求类型
        $tag        = $config['type']['tag']; //自定义请求标签

        //原始请求类型需和用于模拟的类型一致时，并且存在模拟的字段才有效
        if (strcasecmp($substitute, self::$rawMethod) === 0 && isset(self::$data[self::$rawMethod][$tag])) {
            self::$method              = strtoupper(self::$data[self::$rawMethod][$tag]); //赋值更新当前请求类型
            self::$data[self::$method] = self::$data[self::$rawMethod]; //赋值参数
            unset(self::$data[self::$method][$tag]); //去掉标签
            unset(self::$data[self::$rawMethod]); //释放掉原参数

            if (self::$rawMethod == 'POST') {
                $_POST = [];
            } else if (self::$rawMethod == 'GET') {
                $_GET = [];
            }
        }

        self::$status = 1;
    }
    private static function handleUploads($config)
    {
        //扩展UPLOAD错误码
        if (empty($_FILES)) {
            return;
        }

        //数据预处理
        $data = [];
        foreach ($_FILES as $name => $item) {
            foreach ($item as $key => $value) {
                if (is_array($value)) {
                    if (!isset($data[$name])) {
                        $data[$name] = [];
                    }
                    foreach ($value as $index => $value2) {
                        if (!isset($data[$name][$index])) {
                            $data[$name][$index] = [];
                        }
                        $data[$name][$index][$key] = $value2;
                    }
                } else {
                    $data[$name] = [$item];
                }
            }
        }

        //检查文件
        $path = rtrim($config['path'], '/') . '/';
        if (!file_exists($path) && !mkdir($path, 0750, true)) {
            Linker::Exception()::throw ('目录创建失败', 1, 'Request', $path);
        }
        $error = [];
        foreach ($data as $name => &$item) {
            foreach ($item as $index => &$value) {
                $err = self::checkUploads($value, $path, $config);
                if ($err) {
                    if (!isset($error[$name])) {
                        $error[$name] = [];
                    }
                    $error[$name][$index] = $err;
                    unset($data[$name][$index]);
                    if (empty($data[$name])) {
                        unset($data[$name]);
                    }
                }
            }
        }

        self::$uploads['error'] = $error;
        self::$uploads['ok']    = $data;

    }

    private static function checkUploads(&$value, $path, $config)
    {
        //上传错误
        if ($value['error'] !== self::UPLOAD_OK) {
            return $value['error'];
        }

        //上传成功进一步检查
        //非上传文件
        if (!is_uploaded_file($value['tmp_name'])) {
            return self::UPLOAD_NOT_UPLOADED_FILE;
        }
        //自定义验证不过
        if (!call_user_func_array($config['filter'], [$value['type'], $value['size']])) {
            return self::UPLOAD_INVALID;
        }
        //无法移动文件
        if (is_callable($config['rename'])) {
            $name = call_user_func($config['rename'], $value['name']);
        } else {
            $name = $value['name'];
        }

        if (!@move_uploaded_file($value['tmp_name'], $path . $name)) {
            return self::UPLOAD_CANT_MOVE;
        }
        $value = [
            'name' => $name, 'type' => $value['type'], 'size' => $value['size'],
            'file' => $path . $name,
        ];

    }
}
