<?php 
namespace app\common\config;

/*
** 审核权限 控制器
*/

class Audit {

	/**
	** admin用户的级别对应审核显示
	*/
	public static function getLevel ($table) {

		$adminLevel = model('Users')->field(['nickname, level'])->where(['id'=>session('uid')])->find()->toArray();
		$status = model($table)->field(['status'])->where(['is_online'=>1])->select()->toArray();
		
		foreach ($status as $key => $value) {
			$res = $value['status'];
			if ($res == $adminLevel['level']) {
				$level = $value['status'];
			}
		}
		
		return $level;

	}


}