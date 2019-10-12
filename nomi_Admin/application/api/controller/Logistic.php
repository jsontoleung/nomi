<?php
namespace app\api\controller;
use app\common\controller\Apibase;
use think\facade\Request;
use think\facade\Cache;
use think\Db;

class Logistic extends Apibase
{	

    // 优先加载
    public function  initialize() {
        parent::initialize();
    }



    /**
     * 查看物流
     */
    public function Logistic() {

        $id = Request::param('orderid');

        $list = model('OrderMaster')->field('order_id, logistic_name, logistic_sn')->where('order_id=:id', ['id' => $id])->find();


        return view('index', ['list' => $list]);

    }
   



}