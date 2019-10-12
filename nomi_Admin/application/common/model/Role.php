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
** 后台角色表 模型
*/

class Role extends Common {
	
	// 获取角色列表
	public function roleLists() {
		// 按 id 升序
		$lists = $this -> order('id asc') -> select();
		
		return $lists;
	}
	
	// 获取单个角色详情
	public function getRoleDetail($id) {
		$lists = $this -> find($id);
		
		if($lists){
			return $lists;
		}
		return false;
	}
	
	// 获取角色名称
	public function getRoleName($id) {
		// 一维数组
		$name = $this -> where(array('id' => $id)) -> value('name');
		
		if($name){
			return $name;
		}
		return '未分配';
	}
	
	// 删除角色
	public function deletes($id) {
		
		
	}
	
	// 编辑状态
	public function editStatus($id) {
		// 不能修改 超级管理员
		if($id == 1){
			return false;
		}
		
		$lists = $this -> find($id);
		
		if($lists){
			$status = $lists['status'] ? 0 : 1;
			
			$result = $this -> save(array('status' => $status), array('id' => $id));
			if($result){
				return true;
			}
		}
		return false;
	}
	
}
