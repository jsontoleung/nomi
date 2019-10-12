<?php
namespace app\api\controller;
use app\common\config\ProductAllot;
use think\exception\HttpResponseException;
use think\facade\Request;
use think\Response;
use lib\WeixinPay;
use think\Db;
use Log;


/**
 * 商品支付订单
 */
class Pay {

	
	/**
	 * @author 支付订单
	 * @param $proid 	=> 产品id
	 * @param $buyNum 	=> 购买商品数量
	 * @param $count 	=> 购买总价格
	 * @param $openid 	=> openid
	 */
	public function pay() {

		if (Request::isPost()) {

			$proid = Request::post('proid');
			$buyNum = Request::post('buyNum');
			$count = Request::post('count');
			$openid = Request::post('openid');

			// 库存表
			$ware_pro = model('WarehouseProduct')->where('proid=:id', ['id' => $proid])->find();
			$ware['current_cnt'] = $ware_pro['current_cnt'] - $buyNum;
			if ($buyNum > $ware_pro['current_cnt']) {
				return json(['status'=>0, 'msg' => '库存不足']);
			}

			// 购买用户
	    	$user = model('User')->field('user_id, channel_id')->where('openid=:id', ['id'=>$openid])->find();
	    	if (empty($user)) {
	    		return json(['status'=>0, 'msg' => '没有该用户数据']);
	    	}

	    	// 收货地址
	    	$region = model('UserAddr')
				->field('name, phone, province, city, area, address')
				->where('uid=:id', ['id' => $user['user_id']])
				->order(['is_detault' => 1])
				->find();
			if (empty($region)) {
	    		return json(['status'=>0, 'msg' => '请选择收货地址']);
			}


			$appid = APPID;							// 小程序appid
			$openid = $openid;						// 用户openid
			$mch_id = MCHID;						// 商户号
			$key = KEY;								// 商户密钥
			$out_trade_no = $mch_id.time();			// 订单号
			$total_fee = $count;					// 订单金额
			$notify_url = 'https://www.nomiyy.com/index.php/api/pay/notify';
			if ($total_fee) {
				$body = "糯米芽支付";
	            $total_fee = floatval($total_fee * 100);
			}

			$weixinpay = new WeixinPay($appid,$openid,$mch_id,$key,$out_trade_no,$body,$total_fee,$notify_url);
	        $return = $weixinpay->pay();

	        if ($return) {

				$inputs['order_sn'] = $out_trade_no;
				$inputs['channel_id'] = $user['channel_id'];
				$inputs['uid'] = $user['user_id'];
				$inputs['proid'] = $proid;
				$inputs['w_id'] = $ware_pro['wp_id'];
				$inputs['product_cnt'] = $buyNum;
				$inputs['shipping_user'] = $region['name'];
				$inputs['shipping_phone'] = $region['phone'];
				$inputs['province'] = $region['province'];
				$inputs['city'] = $region['city'];
				$inputs['area'] = $region['area'];
				$inputs['address'] = $region['address'];
				$inputs['payment_method'] = 5;
				$inputs['order_money'] = $count;
				$inputs['order_point'] = round($count, 0);
				$inputs['create_time'] = time();
				$inputs['pay_status'] = 0;
				$inputs['order_status'] = 0;

				Db::startTrans();
				try {
					// 添加订单主表信息
					$add_master = model('orderMaster')->data($inputs)->save();
					// 购买后减少库存
					$minusWare = model('WarehouseProduct')->save($ware, ['proid' => $proid]);
					if ($add_master && $minusWare) {
						Db::commit();
						// 这里返回数据
						return json(['status'=>1, 'data' => $return, 'order_sn' => $out_trade_no]);
					}
					
				} catch (Exception $e) {
					Db::rollback();
	                throw $e;
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

        //将xml格式转换成数组
        function xmlToArray($xml) {
            //禁止引用外部xml实体
            libxml_disable_entity_loader(true);
            $xmlstring = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
            $val = json_decode(json_encode($xmlstring), true);
            return $val;
        }
        $attr = xmlToArray($postXml);
        $total_fee = $attr['total_fee'];		// 支付回调金额
        $open_id = $attr['openid'];				// openid
        $out_trade_no = $attr['out_trade_no'];	// 订单号
        $time = $attr['time_end'];				// 回调时间
        Log::write($attr);

        // 查找订单信息和分销商
        $orderMaster = model('OrderMaster')
            ->alias('om')
            ->field('om.order_id, om.order_sn, om.channel_id, om.uid, om.proid, cr.*')
            ->Join('ChannelRetail cr', ['om.channel_id = cr.channel_id'])
            ->where('order_sn=:sn', ['sn' => $out_trade_no])
            ->find();

         // 当前用户信息
    	$user = model('user')
    		->field('user_id, pid, integral, level')
    		->where('user_id=:id', ['id' => $orderMaster['uid']])
    		->find();

    	// 查找上级
    	$topUser = model('user')
    		->field('user_id, pid, integral, team_integral, return_money')
    		->where('user_id=:id', ['id' => $user['pid']])
    		->find();

    	// 查找上上级
		$parentUser = model('user')
    		->field('user_id, integral, team_integral, return_money')
    		->where('user_id=:id', ['id' => $topUser['pid']])
    		->find();

        // 商品信息
        $proInfo = model('product')->field('give_two ,give_one, is_member, level')->where('pro_id=:id', ['id' => $orderMaster['proid']])->find();

        ProductAllot::moneyReturn($orderMaster, $user, $topUser, $parentUser, $proInfo, $total_fee);

        echo '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
		exit();	
	}


}