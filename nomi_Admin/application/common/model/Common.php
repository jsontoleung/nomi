<?php
namespace app\common\model;
use think\Model;

/*
** 公共模型
*/

class Common extends Model {

	// 条件查询（多条记录）
	public function selectWhere ($table, $field='*', $where) {

		$list = model("$table")->field($field)->where($where)->select();
		return $List;
	}


	// 条件查询（单条记录）
	public function findWhere ($table, $field='*', $where) {

		$list = model("$table")->field($field)->where($where)->find();
		return $List;
	}



	// 多条查询
	public function selectAll ($table, $field='*') {

		$list = model("$table")->field($field)->select();
		return $list;

	}




	// 查询单个字段
	public function findValue ($table, $where, $value) {

		$list = model("$table")->where($where)->value("$value");
		return $list;

	}

	
}
