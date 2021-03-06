<?php
/**
 * 安装向导
 */
header('Content-type:text/html;charset=utf-8');
// 检测是否安装过
if (file_exists('./install.lock')) {
    echo '你已经安装过该系统，重新安装需要先删除./public/install.lock 文件';
    die;
}
// 同意协议页面
if(@!isset($_GET['c']) || @$_GET['c']=='agreement'){
    require './install/agreement.html';
}
// 检测环境页面
if(@$_GET['c']=='test'){
    require './install/test.html';
}
// 创建数据库页面
if(@$_GET['c']=='create'){
    require './install/create.html';
}
// 安装成功页面
if(@$_GET['c']=='success'){
    // 判断是否为post
    if($_SERVER['REQUEST_METHOD']=='POST'){
        $data=$_POST;
        // 连接数据库
       $link=new mysqli("{$data['DB_HOST']}:{$data['DB_PORT']}",$data['DB_USER'],$data['DB_PWD']);
       // 获取错误信息
       $error=$link->connect_error;
       if (!is_null($error)) {
           // 转义防止和alert中的引号冲突
           $error=addslashes($error);
           die("<script>alert('数据库链接失败:$error');history.go(-1)</script>");
       }
       // 设置字符集
       $link->query("SET NAMES 'utf8'");
       $link->server_info>5.0 or die("<script>alert('请将您的mysql升级到5.0以上');history.go(-1)</script>");
       // 创建数据库并选中
       if(!$link->select_db($data['DB_NAME'])){
           $create_sql='CREATE DATABASE IF NOT EXISTS '.$data['DB_NAME'].' DEFAULT CHARACTER SET utf8;';
           $link->query($create_sql) or die('创建数据库失败');
           $link->select_db($data['DB_NAME']);
       }
        // TODO 导入sql数据并创建表
       $sql_file = file_get_contents('./install/siam_admin.sql');
       $sql_array=preg_split("/;[\r\n]+/", str_replace('siam_',$data['DB_PREFIX'],$sql_file));
       foreach ($sql_array as $k => $v) {
           if (!empty($v)) {
               $link->query($v);
           }
       }
       $link->close();
        // TODO 写入.env
        $app_name = $data['APP_NAME'];
        $api      = $data['API'] ?? 'http://127.0.0.1:8080/index.php/';

        $env = <<<env
APP_DEBUG = true

[APP]
DEFAULT_TIMEZONE = Asia/Shanghai
APP_NAME = {$app_name}
SIAM_PLUGS = true

[DATABASE]
TYPE = mysql
HOSTNAME = {$data['DB_HOST']}
DATABASE = {$data['DB_NAME']}
USERNAME = {$data['DB_USER']}
PASSWORD = {$data['DB_PWD']}
HOSTPORT = {$data['DB_PORT']}
CHARSET = utf8
DEBUG = true
prefix = {$data['DB_PREFIX']}

[LANG]
default_lang = zh-cn
env;
        file_put_contents("../.env", $env);
        // 替换前端index文件和config
        $index_tpl = file_get_contents("./admin/index.html");
        $index_tpl = str_replace("__项目名__", $app_name, $index_tpl);
        file_put_contents("./admin/index.html", $index_tpl);

        // 替换login模板 title
        $login_tpl = file_get_contents("./admin/login.html");
        $login_tpl = str_replace("__APP_NAME__", $app_name, $login_tpl);
        file_put_contents("./admin/login.html", $login_tpl);

        $setter_tpl = file_get_contents("./admin/js/lay-module/setter.js");
        $setter_tpl = str_replace("__项目名__", $app_name, $setter_tpl);
        $setter_tpl = str_replace("__API__", $api, $setter_tpl);
        file_put_contents("./admin/js/lay-module/setter.js", $setter_tpl);

        touch('./install.lock');
        require './install/success.html';
    }

}
