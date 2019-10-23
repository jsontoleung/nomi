<?php
namespace app\common\model;
use think\Model;

/*
** 订单模型
*/

class Order extends Model {

	// 商品订单信息表
	public function proInfo () {

		$list = model('OrderMaster')
			->alias('om')
			->field('om.order_id, om.order_sn, u.nickname, pro.pro_id, pro.name, om.product_cnt, om.shipping_user, om.shipping_phone, p.province, c.city, a.area, om.address, om.order_money, om.payment_money, om.pay_time, om.order_status')
			->leftJoin('user u', ['u.user_id = om.uid'])
			->leftJoin('product pro', ['pro.pro_id = om.proid'])
			->leftJoin('provinces p', ['p.provinceid = om.province'])
			->leftJoin('city c', ['c.cityid = om.city'])
			->leftJoin('areas a', ['a.areaid = om.area'])
			->where('om.order_status', 'gt', 0)
			->where('om.type', 'eq', 0)
			->order('om.order_id desc')
			->select();
		foreach ($list as $k => $v) {
			$list[$k]['pay_time'] = date('Y年m月d日 H时i分s秒', $v['pay_time']);
		}

		return $list;

	}



	// 服务订单信息表
	public function serveInfo () {

		$list = model('OrderMaster')
			->alias('om')
			->field('om.order_id, om.order_sn, om.product_cnt, om.order_money, om.payment_money, om.pay_time, u.nickname, pro.pro_id, pro.name, sg.phone, sg.name as sg_name, sg.store, sg.make_time, sg.is_sign_in, sg.is_sign_in_time')
			->leftJoin('user u', ['u.user_id = om.uid'])
			->leftJoin('product pro', ['pro.pro_id = om.proid'])
			->leftJoin('serve_goods sg', ['sg.order_id = om.order_id'])
			->where('om.order_status', 'gt', 0)
			->where('om.type', 'eq', 1)
			->order('om.order_id desc')
			->select();

		foreach ($list as $k => $v) {
			$v['pay_time'] = date('Y年m月d日 H时i分s秒', $v['pay_time']);
			$v['make_time'] = date('Y年m月d日 H时i分s秒', $v['make_time']);
			if (!empty($v['is_sign_in_time'])) {
				$v['is_sign_in_time'] = date('Y年m月d日 H时i分s秒', $v['is_sign_in_time']);	
			}
		}

		return $list;

	}



	// 预约列表
	public function appointInfo () {

		$uid = session('uid');

		if ($uid == 1) {
			
			$list = model('OrderMaster')
				->alias('om')
				->field('om.order_sn, p.name as pname, u.nickname, sg.*')
				->leftJoin('product p', ['om.proid = p.pro_id'])
				->leftJoin('user u', ['om.uid = u.user_id'])
				->leftJoin('serve_goods sg', ['om.order_id = sg.order_id'])
				->where('om.type', 'eq', 1)
				->order('sg.is_sign_in')
				->select();

		} else {

			$shop = model('Shop')->where('admin_id=:id', ['id' => $uid])->find();

			if ($shop) {
				
				$list = model('OrderMaster')
					->alias('om')
					->field('om.order_sn, p.name as pname, u.nickname, sg.*')
					->leftJoin('product p', ['om.proid = p.pro_id'])
					->leftJoin('user u', ['om.uid = u.user_id'])
					->leftJoin('serve_goods sg', ['om.order_id = sg.order_id'])
					->where(['om.type' => 1])
					->where(['sg.shopid' => $shop['shop_id']])
					->order('sg.is_sign_in')
					->select();

			}

		}

		foreach ($list as $k => $v) {
			$v['make_time'] = date('Y-m-d H:i', $v['make_time']);
			if (!empty($v['is_sign_in_time'])) {
				$v['is_sign_in_time'] = date('Y年m月d日 H时i分s秒', $v['is_sign_in_time']);	
			}
		}

		return $list;

	}


	
}
