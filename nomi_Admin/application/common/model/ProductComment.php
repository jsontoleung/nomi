<?php
namespace app\common\model;
use think\Model;

/*
** 产品评论表模型
*/

class ProductComment extends Model {
	protected $table = 'nomi_product_comment';


	public function commentInfo ($proid) {

		$list = $this
			->field('com.comment_id, com.evaluate, com.pro_id, com.title, com.content, com.audit_status, com.audit_time, com.update_time, or.order_sn, pro.name, u.nickname')
			->alias('com')
			->leftJoin('order_master or', 'com.order_id = or.order_id')
			->leftJoin('product pro', 'com.pro_id = pro.pro_id')
			->leftJoin('user u', 'com.uid = u.user_id')
			->where('com.pro_id=:id', ['id' => $proid])
			->select();

		return $list;
	}


	
}// --end
