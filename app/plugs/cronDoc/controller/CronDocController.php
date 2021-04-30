<?php

namespace app\plugs\cronDoc\controller;


use app\exception\ErrorCode;
use app\plugs\PlugsBaseController;
use think\helper\Str;

class CronDocController extends PlugsBaseController
{
    public function get_list()
    {
        $file_path = app_path().DIRECTORY_SEPARATOR."cron";
        $list = scandir($file_path);
        unset($list[0]);
        unset($list[1]);
        $list = array_values($list);
        $return = [];
        $namespace = 'app\\cron\\';
        foreach ($list as $file_name){
            $class_name = rtrim($file_name, '.php');
            if ($class_name === 'CronBase') continue;
            $class_namespace = $namespace.$class_name;
            /** @var \app\cron\CronBase $class */
            $class = new $class_namespace;
            $return[] = [
                'name'           => $class->rule(),
                'run_expression' => $class->run_period()->getExpression(),
                'next_run_time'  => $class->run_period()->getNextRunDate()->format("Y-m-d H:i:s"),
                'class_name'     => $class_name,
            ];
        }
        return $this->send(ErrorCode::SUCCESS,['list'=>$return],'SUCCESS');
    }

    /**
     * 在线开关
     * @param array $className 类名 一维数组 ['classname']
     * @return bool|\think\response\Json
     */
    public function online_switch()
    {
        $this->validate(['className'=>'require'],input());
        $file      = runtime_path() . 'cron_status.php';
        $className = input('className');
        $content = <<<EOL
<?php
#start
return $className;
#end
EOL;
        if (!file_exists($file)) {
            $write   = file_put_contents($file, $content);
            if (!$write) {
                return $this->send(ErrorCode::FILE_WRITE_FAIL, [], 'FILE_WRITE_FAIL');
            }
            return $this->send(ErrorCode::SUCCESS, [], 'SUCCESS');
        }
        //更新文件
        $content = preg_replace('/#start.*#end/m', $className, $content);
        $write   = file_put_contents($file, $content);
        if (!$write) {
            return $this->send(ErrorCode::FILE_WRITE_FAIL, [], 'FILE_WRITE_FAIL');
        }
        return $this->send(ErrorCode::SUCCESS, [], 'SUCCESS');
    }

}