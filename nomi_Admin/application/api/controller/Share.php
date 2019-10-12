<?php
namespace app\api\controller;
use app\common\controller\Apibase;
use think\facade\Request;
use think\facade\Cache;

class Share extends Apibase
{	

    // 优先加载
    public function  initialize() {
        parent::initialize();
    }



    // 分享下级
    public function userinfo() {


    	$user = model('user')->field('user_id')->where('user_id=:id', ['id' => $this->uid])->find();

    	if (!empty($user)) {
    		
            $shareUid = Request::param('shareUid');
    		$data['pid'] = $shareUid;
    		if (model('user')->save($data, ['user_id' => $user['user_id']])) {
    			return json(['status' => 1, 'msg' => '恭喜你！成为我们的一员']);
    		}

    	}
    	return json(['status' => 0, 'msg' => '分享失败']);
    	
    }
   



}