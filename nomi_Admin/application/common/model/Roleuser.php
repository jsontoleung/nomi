<?php
namespace app\common\model;
use app\common\model\Common;

// +----------------------------------------------------------------------
// | VenusCMF
// +----------------------------------------------------------------------
// | Copyright (c) 2017-2099
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 水蕃茄 <lzhf237@126.com>
// +----------------------------------------------------------------------

/*
** 后台用户角色对应表 模型
*/

class Roleuser extends Common {
	
	// 新增/更新角色用户
	public function writeRoleUser($data) {
		$find = $this -> where(array('uid' => $data['uid'])) -> find();
		if($find){
			// 删除旧数据
			$this -> where(array('id' => $find['id'])) -> delete();
		}
		$this -> save($data);
	}
	
	// 获取用户对应的角色id
	public function getUserRoleId($uid) {
		// 一维数组
		$role_id = $this -> where(array('uid' => $uid)) -> value('role_id');
		
		if($role_id){
			return $role_id;
		}
		return false;
	}
	
}
