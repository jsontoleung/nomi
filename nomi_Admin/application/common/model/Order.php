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
			->order('om.order_id desc')
			->select();
		foreach ($list as $k => $v) {
			$list[$k]['pay_time'] = date('Y年m月d日 H时i分s秒', $v['pay_time']);
		}

		return $list;

	}


	
}
