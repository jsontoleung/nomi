<?php
namespace app\common\model;
use app\common\model\Common;


/*
** 后台权限授权表 模型
*/

class Authaccess extends Common {
	
	// 获取某个角色已经分配的权限
	public function getHavingAuth($role_id) {
		$lists = $this -> where(array('role_id' => $role_id)) -> column('rule_name');
		
		$roleArray = array();
		if($lists){
			foreach($lists as $key => $val){
				$roleArray[] = strtolower($val);
			}
		}
		
		unset($lists);
		return $roleArray;
	}
}
