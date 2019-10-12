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
** 后台菜单表 模型
*/

class Menu extends Common {
	
	// 注册模型事件
	protected static function init() {
		// 新增后事件
		Menu::afterInsert(function($_menu) {
			// 获取传入的数据
			$data = $_menu;
			
			// 处理 path、topid
			$id = $data['id'];
			$pid = $data['pid'];
			if($pid > 0){
				// 获取上级菜单的 path
				$prentPath = $_menu -> where(array('id' => $pid)) -> value('path');
				
				$pPath = explode('-', $prentPath);
				
				$newData['path'] = $prentPath . '-' . $id;
				// 0-1 中 1 的位置，即本系列首个菜单
				$newData['topid'] = $pPath[1];
			}else{
				$newData['path']  = '0-' . $id;
				$newData['topid'] = 0;
			}
			
			// 保存
			$_menu -> save($newData, array('id' => $id));
			
			// 更新缓存
			$_menu -> reCache();
		});
		
		// 更新后事件
		Menu::afterUpdate(function($_menu) {
			// 更新缓存
			$_menu -> reCache();
		});
	}
	
	// 更新缓存
	protected function reCache() {
		cache('Menu', null);
		
		$data = $this -> menuLists();
		cache('Menu', $data);
	}
	
	// 获取菜单列表
	public function menuLists() {
		// 按 path 升序，结果是对象
		$lists = $this -> order('path', 'asc') -> select();
		// 对象转换为数组
		$lists = object2array($lists);
		
		// 组合数据
		foreach($lists as $key => &$val){
			$val['icon'] 	= $val['icon'] ? $val['icon'] : 'fa-desktop';
			$val['url'] 	= $val['module'] . '/' . $val['control'] . '/' . $val['actions'];
			unset($val['module']);
			unset($val['control']);
			unset($val['actions']);
		}
		
		return $lists;
	}
	
	// 获取所有顶级菜单
	public function getTopMenu() {
		
		$field = array('id', 'pid', 'topid', 'module', 'control', 'actions', 'name');
		
		// 按 path 升序
		$lists = $this -> where(array('pid' => 0)) -> field($field) -> order('path asc') -> select();
		
		return $lists;
	}
	
	// 根据 topid 获取所有的子菜单
	public function getAllChild($id) {
		$field = array('id', 'pid', 'topid', 'path', 'module', 'control', 'actions', 'name');
		
		// 按 path 升序
		$lists = $this -> where(array('topid' => $id)) -> field($field) -> order('path asc') -> select();
		
		// 处理一级菜单路径，为 下级的全选 / 全不选作铺垫
		foreach($lists as $key => &$val){
			$paths = explode('-', $val['path']);
			$val['mytopid'] = $paths[2];
		}
		
		return $lists;
	}
	
	// 获取单个菜单详情
	public function getMenuDetail($id) {
		$lists = $this -> find($id);
		
		if($lists){
			// 获取上级菜单名称
			$lists['pname'] = $this -> getPidName($lists['pid']);
			return $lists;
		}
		
		return false;
	}
	
	// 获取上级菜单名称
	protected function getPidName($id) {
		if($id == 0){
			return '作为顶级菜单';
		}
		
		$pName = $this -> where(array('id' => $id)) -> value('name');
		if($pName){
			return $pName;
		}
		
		return 'unKnown';
	}
	
	// 获取左侧权限菜单，仅支持 3 级
	public function sidebar() {
		$lists = cache('Menu');
		$uid = session('uid');
		
		if(!$lists){
			$lists = $this -> menuLists();
			
			// 更新缓存
			cache('Menu', $lists);
		}
		
		// 取出 status = 1 的
		$showMenu = array();
		foreach($lists as $key => $val){
			if($val['status'] == 1){
				$showMenu[] = $val;
			}
		}
		unset($lists);
		
		// 获取权限菜单
		$authMenu = array();
		if($uid == 1){
			// 如果是超级管理员
			$authMenu = $showMenu;
		}else{
			// 检查用户合法性、获取权限列表
			$managerLawful = check_manager_lawful($uid);
			if($managerLawful){
				if($managerLawful['role_id'] == 1){
					// 如果是超级管理员组
					$authMenu = $showMenu;
				}else{
					foreach($showMenu as $val){
						$rule_name = strtolower($val['url']);
						
						// 权限验证是否具有菜单权限
						if(auth_check($uid, $managerLawful['role_access'], $rule_name)){
							$authMenu[] = $val;
						}
						
					}
				}
			}else{
				$authMenu = array();
			}
		}

		// 处理菜单树
		$menuLists = array();
		foreach($authMenu as $keyTop => $valTop){

			// 顶级菜单
			if($valTop['pid'] == 0){
				$menuLists[$keyTop] = arraySort($valTop, $valTop['sort']);

				// 一级菜单
				foreach($authMenu as $keyFir => $valFir){

					if($valFir['pid'] == $valTop['id']){

						// 二级菜单
						foreach($authMenu as $keySec => $valSec){
							if($valSec['pid'] == $valFir['id']){
								$valFir['child'][] = $valSec;
							}
						}
						$menuLists[$keyTop]['child'][] = $valFir;
					}

				}

			}

		}
		
		if (empty($menuLists)) {
			return false;
		}
		foreach ($menuLists as $k => $v) {
			$tmp[$k] = $v['sort'];
		}
		array_multisort($tmp, SORT_ASC, $menuLists);
		
		return $menuLists;
	}
	
	// 检测是否有子菜单
	public function hasChild($id) {
		
		$has = $this -> where(array('pid' => $id)) -> value('id');
		if($has){
			return true;
		}
		return false;
	}
	
	// 删除菜单
	public function deletes($id) {
		
		// 验证 id
		$find = $this -> where(array('id' => $id)) -> value('id');
		if(!$find){
			return array('status' => 0, 'msg' => '参数错误');
		}
		
		// 检测是否有子菜单
		if($this -> hasChild($id)){
			return array('status' => 0, 'msg' => '有子菜单，不能删除');
		}
		
		// 执行删除
		if($this -> where(array('id' => $id)) -> delete()){
			return array('status' => 1, 'msg' => '删除成功，请更新角色权限');
		}
		return array('status' => 0, 'msg' => '删除失败');
	}
	
}
