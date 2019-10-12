<?php
namespace app\common\validate;
use think\Validate;
use \think\Loader;


/*
** 文章分类表 模型验证器
*/

class Category extends Validate {
	
	// 验证规则
	protected $rule = [
			'pid' 		=> 'number',
			'pid' 		=> 'checkPid', 			// 自定义函数验证
			'pid' 		=> 'checkPathLevel', 	// 自定义函数验证
			'name' 		=> 'require',
			'status' 	=> 'in:0,1',
		];
	
	// 返回对应信息
	protected $message = [
			'pid.number' 			=> '请选择上级分类',
			'pid.checkPid' 			=> '上级分类不存在',
			'pid.checkPathLevel' 	=> '分类只支持 5 级',
			'name.require' 			=> '分类名称不能为空',
			'status.in' 			=> '状态值范围不正确',
		];
	
	// 验证场景
	protected $scene = [
			'add' 	=> ['pid', 'name', 'status'],
			'edit' 	=> ['pid', 'name', 'module', 'status']
		];
	
	/*
	** 验证上级分类是否存在
	** 可以传入的参数共有5个（后面三个根据情况选用），依次为：
	** 验证数据、验证规则、全部数据（数组）、字段名、字段描述
	*/
	protected function checkPid($pid, $rule, $data) {
		if($pid == 0){
			return true;
		}
		
		// 调用模型验证数据，此处验证 pid 大于 0 的数据
		$find = model('Category') -> where(array('id' => $pid)) -> value('id');
		if($find){
			return true;
		}
		return false;
	}
	
	// 验证分类深度
	protected function checkPathLevel($pid) {
		if($pid == 0){
			return true;
		}
		
		// 大于 0 时，获取上级菜单的 path
		$prentPath = model('Category') -> where(array('id' => $pid)) -> value('path');
		if($prentPath){
			$pPath = explode('-', $prentPath);
			// 处理 level
			$level = count($pPath) - 1;
			if($level > 4){
				return false;
			}
			return true;
		}
		return false;
	}
	
}
