<?php
namespace app\common\model;
use think\Model;

/*
** 产品服务模型
*/

class Serve extends Model {

	public function serveInfo () {
			
		$list = model('Product')
			->field('p.pro_id, p.cid, p.name, p.pro_number, p.price_before, p.price_after, p.photo, p.give_one, p.give_two, p.combo, p.volume, p.buyNum, p.end_time, p.is_member, p.sort, p.is_down, l.level_type, c.name as cname')
			->alias('p')
			->leftJoin('user_level l', 'p.level = l.level_id')
			->leftJoin('category c', 'p.cid = c.id')
			->where('p.type=:id', ['id' => 1])
			->order('p.sort desc, p.pro_id desc')
			->select();

		foreach ($list as $k => $v) {
			
			if ($v['end_time'] > 0) $v['end_time'] = date('Y-m-d H:i', $v['end_time']);
			
		}
		
		return $list;
	}



}
