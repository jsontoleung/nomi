<?php
namespace app\api\controller;
use app\common\controller\Apibase;
use think\facade\Request;
use think\facade\Cache;

/**
 * 客服建议
 */
class Offer extends Apibase
{	

    // 优先加载
    public function  initialize() {
        parent::initialize();
    }


    public function index() {

        $data['uid'] = $this->uid;
        $data['content'] = Request::param('content');
        $data['add_time'] = time();

        $add = model('Offer')->data($data)->save();
        if ($add) {
            return json(['status' => 1, 'msg' => '等待客服审核']);
        }

    }
    
   



}