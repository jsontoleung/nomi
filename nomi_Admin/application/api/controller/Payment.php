<?php
namespace app\api\controller;
use app\common\controller\Apibase;
use think\facade\Request;


class Payment extends Apibase {


	// 优先加载
	public function  initialize() {
		parent::initialize();
	}


	// 购买商品信息 /pages/pay/index
	public function home() {

		// 产品id
		$proid = Request::param('proid');
		// 选择购买数量
		$buy_num = 1;

		// 获取收货地址信息
		$addr = model('UserAddr')
			->field('ua.name, ua.phone, ua.address, p.province, c.city, a.area')
			->alias('ua')
			->leftJoin('provinces p', 'ua.province = p.provinceid')
			->leftJoin('city c', 'ua.city = c.cityid')
			->leftJoin('areas a', 'ua.area = a.areaid')
			->where('ua.uid=:id', ['id' => $this->uid])
			->where('ua.is_default=:id', ['id' => 1])
			->find();

		// 当前用户会员优惠价
		$pick_price = model('user')
			->alias('u')
			->field('l.level_id, l.pick_price, l.level_type')
			->leftJoin('user_level l', ['u.level = l.level_id'])
			->where('u.user_id=:id', ['id' => $this->uid])
			->find();

		// 商品信息
		$proInfo = model('product')
			->alias('p')
			->field('p.pro_id, p.type, p.name, p.price_after, p.photo, p.pledge_type, p.combo, p.serve_num, wp.current_cnt')
			->leftJoin('WarehouseProduct wp', 'p.pro_id = wp.proid')
			->where('pro_id=:id', ['id' => $proid])
			->find();

		if ($proInfo['combo'] == 0) {
			$proInfo['price_after'] = sprintf("%.2f", ($proInfo['price_after'] * $pick_price['pick_price']));
		}

		$proInfo['pledge_type'] = parse_config_attr($proInfo['pledge_type']);
		if (substr($proInfo['photo'], 0, 4) !== 'http') {
            $proInfo['photo'] = URL_PATH . $proInfo['photo'];
        }

		$showDatas = array(
            'buy_num'      	=> $buy_num,
            'addr'         	=> $addr,
            'proInfo'       => $proInfo,

        );
        return json($showDatas);

	}



	/**
	 * 支付成功返回页面
	 * $orderSn		=> 订单号
	 */
	public function detail($order_sn) {

		$order = model('OrderMaster')
			->field('om.order_id, om.order_sn, om.create_time, om.pay_time, om.shipping_user, om.shipping_phone, pr.province, ci.city, ar.area, om.address, om.payment_money, pro.name as pro_name, pro.price_after, pro.photo, pro.pledge_type')
			->alias('om')
			->leftJoin('provinces pr', 'om.province = pr.provinceid')
			->leftJoin('city ci', 'om.city = ci.cityid')
			->leftJoin('areas ar', 'om.area = ar.areaid')
			->leftJoin('product pro', 'om.proid = pro.pro_id')
			->where('order_sn=:sn', ['sn' => $order_sn])
			->select();
		if ($order) {

			foreach ($order as $k => $v) {
				
				if (substr($v['photo'], 0, 4) !== 'http') $v['photo'] = URL_PATH . $v['photo'];
				$order[$k]['pledge_type'] = parse_config_attr($v['pledge_type']);
				$order[$k]['create_time'] = date("y-m-d H:i:s", $v['create_time']);
				$order[$k]['pay_time'] = date("y-m-d H:i:s", $v['pay_time']);

			}
			
			return json(['order' => $order]);

		}



	}


	


}