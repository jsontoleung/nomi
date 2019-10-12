<?php
namespace app\common\model;
use think\Model;

/*
** 用户地址表模型
*/

class UserAddr extends Model {
	protected $table = 'nomi_user_addr';


	/**
	 * 收货地址页面
	 */
	public function addrInfo ($uid) {

		$lists = model('UserAddr')
			->field('a.addr_id, a.zip, a.address, a.is_default, u.nickname, p.province, c.city, ar.area')
			->alias('a')
			->leftJoin('user u', 'a.uid = u.user_id')
			->leftJoin('provinces p', 'a.province = p.provinceid')
			->leftJoin('city c', 'a.city = c.cityid')
			->leftJoin('areas ar', 'a.area = ar.areaid')
			->where('uid=:id', ['id' => $uid])
			->select();
		return $lists;
	}



}
