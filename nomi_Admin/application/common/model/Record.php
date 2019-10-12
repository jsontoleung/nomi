<?php
namespace app\common\model;
use think\Model;

/*
** 记录管理模型
*/

class Record extends Model {


	/**
	 * 会员订单信息
	 */
	public function memberList ($channel_id = '') {

		if (empty($channel_id)) {
			
			$list = model('MemberOrder')
				->alias('mo')
				->field('mo.order_id, mo.order_sn, u.nickname, l.level_type, mo.order_money, mo.payment_money, mo.one_uid, mo.one_level_money, mo.one_level_integral, mo.second_uid, mo.second_level_money, mo.second_level_integral, mo.pay_time, cl.channel_name, mr.*')
				->leftJoin('user u', ['mo.uid = u.user_id'])
				->leftJoin('user_level l', ['mo.level_id = l.level_id'])
				->leftJoin('user uo', ['mo.one_uid = uo.user_id'])
				->leftJoin('user ut', ['mo.second_uid = ut.user_id'])
				->leftJoin('channel cl', ['mo.channel_id = cl.channel_id'])
				->leftJoin('member_retail mr', ['mr.order_id = mo.order_id'])
				->where(['mo.pay_status' => 1])
				->order('mo.order_id desc')
				->select();

		} else {

			$list = model('MemberOrder')
				->alias('mo')
				->field('mo.order_id, mo.order_sn, u.nickname, l.level_type, mo.order_money, mo.payment_money, mo.one_uid, mo.one_level_money, mo.one_level_integral, mo.second_uid, mo.second_level_money, mo.second_level_integral, mo.pay_time, cl.channel_name, mr.*')
				->leftJoin('user u', ['mo.uid = u.user_id'])
				->leftJoin('user_level l', ['mo.level_id = l.level_id'])
				->leftJoin('user uo', ['mo.one_uid = uo.user_id'])
				->leftJoin('user ut', ['mo.second_uid = ut.user_id'])
				->leftJoin('channel cl', ['mo.channel_id = cl.channel_id'])
				->leftJoin('member_retail mr', ['mr.order_id = mo.order_id'])
				->where(['mo.pay_status' => 1])
				->where('mo.channel_id=:id', ['id' => $channel_id])
				->order('mo.order_id desc')
				->select();

		}

		
		foreach ($list as $k => $v) {

			$list[$k]['pay_time'] = date('Y年m月d日 H时i分s秒', $v['pay_time']);
			$list[$k]['one_nickname'] = model('user')->where('user_id=:id', ['id' => $v['one_uid']])->value('nickname');
			$list[$k]['two_nickname'] = model('user')->where('user_id=:id', ['id' => $v['second_uid']])->value('nickname');
			
			if (empty($v['one_nickname'])) {
				$list[$k]['one_nickname'] = '无一级';
			}

			if (empty($v['two_nickname'])) {
				$list[$k]['two_nickname'] = '无二级';
			}

		}
		return $list;
	}



	/**
	 * 查看企业体系
	 */
	public function entSystem ($id) {

		$list = model('MemberRetail')
			->where('order_id=:id', ['id' => $id])
			->find();

		return $list;
	}



	/**
	 * 登陆记录
	 */
	public function loginInfo () {

		$list = model('UserLoginLog')
			->alias('ul')
			->field('u.nickname, ul.login_time, ul.login_ip')
			->leftJoin('user u', ['ul.uid = u.user_id'])
			->order('ul.login_time desc')
			->select();
		return $list;
	}




	/**
	 * 产品购买记录
	 */
	public function productList ($channel_id = '') {

		if (empty($channel_id)) {
			
			$list = model('OrderMaster')
				->alias('om')
				->field('om.order_id, om.order_sn, u.nickname, pro.pro_id, pro.name, om.product_cnt, om.shipping_user, om.shipping_phone, p.province, c.city, a.area, om.address, om.payment_method, om.order_money, om.payment_money, om.give_one, om.give_two, om.pay_time, or.*')
				->leftJoin('user u', ['u.user_id = om.uid'])
				->leftJoin('product pro', ['pro.pro_id = om.proid'])
				->leftJoin('provinces p', ['p.provinceid = om.province'])
				->leftJoin('city c', ['c.cityid = om.city'])
				->leftJoin('areas a', ['a.areaid = om.area'])
				->leftJoin('order_retail or', ['or.order_id = om.order_id'])
				->where(['om.pay_status' => 1])
				->order('om.order_id desc')
				->select();
		} else {

			$list = model('OrderMaster')
				->alias('om')
				->field('om.order_id, om.order_sn, u.nickname, pro.pro_id, pro.name, om.product_cnt, om.shipping_user, om.shipping_phone, p.province, c.city, a.area, om.address, om.payment_method, om.order_money, om.payment_money, om.give_one, om.give_two, om.pay_time, or.*')
				->leftJoin('user u', ['u.user_id = om.uid'])
				->leftJoin('product pro', ['pro.pro_id = om.proid'])
				->leftJoin('provinces p', ['p.provinceid = om.province'])
				->leftJoin('city c', ['c.cityid = om.city'])
				->leftJoin('areas a', ['a.areaid = om.area'])
				->leftJoin('order_retail or', ['or.order_id = om.order_id'])
				->where(['om.pay_status' => 1])
				->where('om.channel_id=:id', ['id' => $channel_id])
				->order('om.order_id desc')
				->select();

		}
		foreach ($list as $k => $v) {
			$list[$k]['pay_time'] = date('Y年m月d日 H时i分s秒', $v['pay_time']);
			switch ($v['payment_method']) {
				case '5':
					$list[$k]['method'] = '微信支付';
					break;

				case '6':
					$list[$k]['method'] = '产品赠送';
					break;
				
				default:
					$list[$k]['method'] = '未知状态';
					break;
			}
		}

		return $list;

	}



}
