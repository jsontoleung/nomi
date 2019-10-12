<?php
namespace app\common\validate;
use think\Validate;
use \think\Loader;

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
** 后台角色表 模型验证器
*/

class Role extends Validate {
	
	// 验证规则
	protected $rule = [
			'name' 	=> 'require',
			'status' 	=> 'in:0,1',
			'id' 	=> 'checkAdmin' 	// 自定义函数
		];
	
	// 返回对应信息
	protected $message = [
			'name.require' 		=> '角色名称不能为空',
			'status.in' 		=> '状态值范围不正确',
			'id.checkAdmin' 	=> '不能编辑超级管理员'
		];
	
	// 验证场景
	protected $scene = [
			'add' 	=> ['name', 'status'],
			'edit' 	=> ['id', 'name', 'status']
		];
	
	// 不能编辑超级管理员
	protected function checkAdmin($id) {
		if($id == 1){
			return false;
		}
		
		return true;
	}
	
}
