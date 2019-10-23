<?php
namespace app\api\controller;
use app\common\config\MemberAllot;
use think\exception\HttpResponseException;
use think\facade\Request;
use think\Response;
use lib\WeixinPay;
use Log;

// 加入我们支付订单
class Memberpay {
	
	/**
	 * @author 会员支付订单
	 * @param $money 	=> 支付金额
     * @param $openid   => openid
	 * @param $level 	=> 购买会员的等级
	 */
	public function pay() {

        if (Request::isPost()) {
            
            $money = Request::post('money');
            $openid = Request::post('openid');
            $level = Request::post('level');

    		$appid = APPID;							// 小程序appid
    		$openid = $openid;						// 用户openid
    		$mch_id = MCHID;						// 商户号
    		$key = KEY;								// 商户密钥
    		$out_trade_no = $mch_id.time();			// 订单号
    		$total_fee = $money;					// 订单金额
    		$notify_url = 'https://www.nomiyy.com/index.php/api/memberpay/notify';
    		if ($total_fee) {
    			$body = "糯米芽支付";
                $total_fee = floatval($total_fee * 100);
    		}

    		$weixinpay = new WeixinPay($appid,$openid,$mch_id,$key,$out_trade_no,$body,$total_fee,$notify_url);
            $return = $weixinpay->pay();

            if ($return) {

            	$user = model('User')->field('user_id, channel_id')->where('openid=:id', ['id'=>$openid])->find();
            	if (empty($user)) {
            		return json(['status'=>0, 'msg' => '没有该用户数据']);
            	}

    			$inputs['order_sn'] = $out_trade_no;
                $inputs['uid'] = $user['user_id'];
                $inputs['channel_id'] = $user['channel_id'];
    			$inputs['level_id'] = $level;
    			$inputs['order_money'] = $money;
    			$inputs['order_integral'] = round($money, 0);
    			$inputs['add_time'] = time();
                $inputs['pay_status'] = 0;
    			$inputs['order_status'] = 0;

    			if (model('MemberOrder')->data($inputs)->save()) {
    				// 这里返回数据
    				return json(['status'=>1, 'data' => $return]);
    			}


            } else {
            	// 这里返回错误码及错误信息，供客户端调用
            	return json(['status'=>0, 'msg' => '没有找到该用户']);
            }

        }

	}


	/**
	 * 支付回调
	 */
	public function notify () {

		error_reporting(E_ALL);
		$postXml = file_get_contents("php://input");
		if (empty($postXml)) {
            return json(['status'=>0, 'msg' => '没有任何参数']);
        }

        $attr = xmlToArray($postXml);
        $total_fee = $attr['total_fee'];		// 支付金额
        $open_id = $attr['openid'];				// openid
        $out_trade_no = $attr['out_trade_no'];	// 订单号
        $time = $attr['time_end'];				// 回调时间
        Log::write($attr);

        // 查找订单信息和分销商
        $memberpay = model('MemberOrder')
            ->alias('om')
            ->field('om.order_id, om.channel_id, om.uid, om.level_id, cr.*')
            ->Join('ChannelRetail cr', ['om.channel_id = cr.channel_id'])
            ->where('order_sn=:sn', ['sn' => $out_trade_no])
            ->find();
        
        // 查找购买用户信息
        $user = model('user')->where('user_id=:id',['id'=>$memberpay['uid']])->find();
        $dataUser['level'] = $memberpay['level_id'];
        $dataUser['integral'] = round($user['integral']+$total_fee, 0);

        // 查询购买会员等级信息
        $userLevel = model('Userlevel')->where('level_id=:id', ['id' => $memberpay['level_id']])->find();

        //查找二级
        $secondUser = model('user')
        	->field('user_id, pid, integral, team_integral, return_money')
        	->where('user_id=:id', ['id' => $user['pid']])
        	->find();
        
        //查询一级
        $oneUser = model('user')
        	->field('user_id, integral, team_integral, return_money')
        	->where('user_id=:id', ['id' => $secondUser['pid']])
        	->find();

        MemberAllot::moneyReturn($memberpay, $total_fee, $userLevel, $secondUser, $oneUser, $dataUser);

        echo '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
		exit();	
	}


}