<?php

namespace app\plugs;

use app\facade\SiamApp;
use app\model\PlugsStatusModel;
use Siam\Component\Di;
use think\Exception;
use think\facade\Db;
use think\helper\Str;

class PlugsInitEvent
{
    public static function app_init()
    {
        //遍历plugs目录,执行已经安装过的plugs init
        $arr = scandir(__DIR__);

        //检查是否在start文件，是否有安装记录
        $path_info = request()->pathinfo();
        $path_info = explode('/', $path_info);
        SiamApp::getInstance()->setModule($path_info[0]);
        $need_init_plugs_list = [];
        foreach ($arr as $dirName) {
            //插件根目录
            $path = __DIR__ . '\\' . $dirName;
            if (Str::contains($path, '.') == true|| !is_dir($path)){
                continue;
            }

            $PlugsClass = __NAMESPACE__ . '\\' . $dirName . '\Plugs';
            $plugs      = new $PlugsClass();
            //获取插件数据
            $name     = $plugs->get_config()->getName();
            //对应模块启动
            $module = $plugs->get_config()->getHandleModule();
            if (!in_array($path_info[0], $module)) {
                continue;
            }
            $need_init_plugs_list[] = $plugs;
        }
        $installed_plugs = PlugsStatusModel::select()->toArray();
        $installed_plugs = array_column($installed_plugs, null, 'plugs_name');

        /** @var \app\plugs\base\Plugs $plugs */
        foreach ($need_init_plugs_list as $plugs)
        {
            //检查安装状态
            if (!isset($installed_plugs[$plugs->get_config()->getName()])) {
                continue;
            }
            $plugs_install_status = $installed_plugs[$plugs->get_config()->getName()];
            //检查启动状态
            if ($plugs_install_status['plugs_status'] != PlugsStatusModel::PLUGS_STATUS_ON) {
                continue;
            }
            //执行init
            $plugs->init();
        }


    }

}