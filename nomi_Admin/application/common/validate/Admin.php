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
** 菜单 模型验证器
*/

class Admin extends Validate {
	
	// 验证规则
	protected $rule = [
			'name' 		=> 'require',
			'nickname' 	=> 'require',
			'passwd' 	=> 'require'
		];
	
	// 返回对应信息
	protected $message = [
			'name.require' 			=> '用户名不能为空',
			'nickname.require' 		=> '昵称不能为空',
			'passwd.require' 		=> '密码不能为空'
		];
	
	// 验证场景
	protected $scene = [
			'add' 	=> ['name', 'nickname', 'passwd'],
			'edit' 	=> ['name', 'nickname']
		];
	
}
