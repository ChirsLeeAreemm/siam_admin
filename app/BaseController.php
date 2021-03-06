<?php
declare (strict_types = 1);

namespace app;

use app\exception\AuthException;
use app\exception\ErrorCode;
use think\App;
use think\exception\ValidateException;
use think\Validate;

/**
 * 控制器基础类
 */
abstract class BaseController
{
    /**
     * Request实例
     * @var \think\Request
     */
    protected $request;

    /**
     * 应用实例
     * @var \think\App
     */
    protected $app;

    /**
     * 是否批量验证
     * @var bool
     */
    protected $batchValidate = false;

    /**
     * 控制器中间件
     * @var array
     */
    protected $middleware = [];


    abstract function get_list();
    abstract function add();
    abstract function edit();
    abstract function delete();
    abstract function auth();

    /**
     * 构造方法
     * @access public
     * @param  App  $app  应用对象
     */
    public function __construct(App $app)
    {
        $this->app     = $app;
        $this->request = $this->app->request;

        // 控制器初始化
        $this->initialize();
    }

    // 初始化
    protected function initialize()
    {
        $this->auth();
    }

    /**
     * 验证数据
     * @access protected
     * @param  string|array     $validate 验证器名或者验证规则数组
     * @param  array|null       $data     数据
     * @param  array        $message  提示信息
     * @param  bool         $batch    是否批量验证
     * @return array|string|true
     */
    protected function validate($validate, $data = null, array $message = [], bool $batch = false)
    {
        if ($data === null) $data = $this->request->param();

        if (is_array($validate)) {
            $v = new Validate();
            $v->rule($validate);
        } else {
            if (strpos($validate, '.')) {
                // 支持场景
                [$validate, $scene] = explode('.', $validate);
            }
            $class = false !== strpos($validate, '\\') ? $validate : $this->app->parseClass('validate', $validate);
            $v     = new $class();
            if (!empty($scene)) {
                $v->scene($scene);
            }
        }

        $v->message($message);

        // 是否批量验证
        if ($batch || $this->batchValidate) {
            $v->batch(true);
        }

        try{
            $res = $v->failException(true)->check($data);
        }catch (\Exception $e){
            throw new AuthException($e->getMessage(), (int)ErrorCode::PARAM_EMPTY);
        }

        return $res;
    }

    /**
     * 响应输出
     * @param        $code
     * @param array  $data
     * @param string $msg
     * @return \think\response\Json
     */
    public function send($code, $data = [], $msg = ''): \think\response\Json
    {
        return json([
            'code' => (int) $code,
            'data' => (object) $data,
            'msg'  => $msg
        ]);
    }
}
