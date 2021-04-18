<?php

namespace app\plugs\httpMonitor\controller;

use app\exception\ErrorCode;
use app\plugs\httpMonitor\model\PlugsHttpMonitorModel as Model;
use app\plugs\PlugsBaseController;

class PlugsHttpMonitorController extends PlugsBaseController
{
    /**
     * @return mixed
     * @throws \think\Exception
     */
    public function get_list()
    {

        $page  = input('page', 1);
        $limit = input('limit', 10);

        $result = Model::paginate(['page' => $page, 'list_rows' => $limit,])->toArray();
        $lists  = $result['data'];
        $count  = $result['total'];
        return $this->send(ErrorCode::SUCCESS,['lists'=>$lists,'count'=>$count]);

    }

    public function get_one()
    {
        $id = input('id');
        $result = Model::find($id);
        if (!$result){
            return $this->send(ErrorCode::THIRD_PART_ERROR,[],'获取失败');
        }
            return $this->send(ErrorCode::SUCCESS,['lists'=>$result]);
    }

    /**
    * @return \think\response\Json
    */
    public function add()
    {

        $param = input();

        $start = Model::create($param);

        if (!$start) {
            return $this->send(ErrorCode::THIRD_PART_ERROR,[],'新增失败');
         }
            return $this->send(ErrorCode::SUCCESS);
    }

    /**
    * @return \think\response\Json
    */
    public function edit()
    {
        $param = input();
        $start = Model::find($param['id']);
        $res   = $start->save($param);

        if (!$res){
            return $this->send(ErrorCode::THIRD_PART_ERROR,[],'编辑失败');
        }
            return $this->send(ErrorCode::SUCCESS);
    }

    /**
    * @return \think\response\Json
    */
    public function delete()
    {
        $id = input('id');

        $result = Model::destroy($id);

        return $this->send(ErrorCode::SUCCESS,[],'ok');
    }
}