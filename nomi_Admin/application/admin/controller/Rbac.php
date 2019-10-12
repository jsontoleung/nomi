<?php
namespace app\admin\controller;
use app\common\controller\Adminbase;
use think\facade\Request;
use think\facade\Cache;
use think\Db;

/*
** Rbac 控制器
*/

class Rbac extends Adminbase {

	// 优先加载
	public function  initialize() {
		parent::initialize();
	}

	// 验证新增 name 是否重复
	private function checkNameAdd($name) {
		
		$find = model('role')->where(array('name' => $name)) -> find();
		if($find){
			return json(['status'=>0, 'msg'=>'同样的数据已经存在']);
		}
		return true;
	}

	// 验证编辑 name 是否重复
	private function checkNameUpdate($data) {
		// 检查是否重复添加
		$id = $data['id'];
		
		$find = model('role')->where(array('name' => $data['name']))->value('id');
		if($find && $find != $id){
			return json(['status'=>0, 'msg'=>'同样的数据已经存在']);
		}
		return true;
	}


	// 角色列表
	public function index () {

		if (Cache::get('roleLists')) {
			$lists = Cache::get('roleLists');
		} else {
			$lists = model('role')->roleLists();
			Cache::set('roleLists', $lists);
		}

		$this->assign('rolelists', $lists);
		return view('index');

	}


	// 添加角色
	public function add () {
		return view('add');
	}


	// 更改角色列表
	public  function edit () {

		$id = Request::param('id');
		if(!$id){
			$this -> error('参数错误');
		}

		// 获取本条角色数据
		$list = model('role')->getRoleDetail($id);
		if(!$list){
			$this -> error('参数错误');
		}

		$this->assign('list', $list);
		return view('edit');
	}


	// 添加、更改接口
	public function save () {

		$inputs = Request::post();
		if (Request::isPost()) {

			if (empty($inputs['id'])) {

				// 使用模型验证器进行验证
				$result = $this->validate($inputs, 'Role.add');
				if(true !== $result){
					// 验证失败 输出错误信息
					return json(['status' => 0, 'msg' => $result]);
				}
				
				// 验证新增角色名称是否重复
				$this->checkNameAdd($inputs['name']);
				
				// 保存数据
				if(model('role')->data($inputs)->save()){
					if (Cache::get('roleLists')) {
						cache('roleLists', null);
					}
					return json(['status'=>1, 'msg'=>'操作成功']);
				}
				
			} else {

				// 使用模型验证器进行验证
				$result = $this->validate($inputs, 'Role.edit');
				if(true !== $result){
					// 验证失败 输出错误信息
					return json(['status' => 0, 'msg' => $result]);
				}

				// 检测本条角色数据是否存在
				$find = model('role')->where(array('id' => $inputs['id']))->value('id');
				if(!$find){
					return json(['status' => 0, 'msg' => '参数错误']);
				}
				
				// 验证编辑角色名称是否重复
				$this->checkNameUpdate($inputs);
				
				// 保存数据
				if(model('role')->save($inputs, array('id' => $inputs['id']))){
					if (Cache::get('roleLists')) {
						cache('roleLists', null);
					}
					return json(['status'=>1, 'msg'=>'操作成功']);
				}

			}

		}

	}


	// 删除角色
	public function deletes() {
		
		$id = Request::param('id');
		if(!$id){
			return json(['status' => 0, 'msg' => '参数错误']);
		}

		$result = model('role')->where(['id' => $id])->delete();
		if($result){
			if (Cache::get('roleLists')) {
				cache('roleLists', null);
			}
			return json(['status'=>1, 'msg'=>'删除成功']);
		}
		return json(['status' => 0, 'msg' => $result['msg']]);
	}



	// 角色权限设置页面
	public function authorize() {
		$role_id = Request::param('id');
		$role_id = $role_id > 0 ? $role_id : 0;
		if(!$role_id){
			$this -> error('角色信息错误');
		}
		// 判断，不能修改 超级管理员
		if($role_id == 1){
			$this -> error('角色信息错误');
		}
		
		// 获取已经分配的权限
		$roleHas = model('Authaccess') -> getHavingAuth($role_id);
		
		// 获取后台顶级菜单
		$topMenu = model('Menu') -> getTopMenu();
		
		if (Cache::get('rbacMenu')) {
			$rbacMenu = Cache::get('rbacMenu');
		} else {

			$rbacMenu = '';
			// 获取子菜单
			foreach($topMenu as $key => $val){
				$childMenu = model('Menu') -> getAllChild($val['id']);
				
				// 顶级菜单
				$rbacMenu .= '<tr><td>';
				$roleTop = strtolower($val['module'] . '/' . $val['control'] . '/' . $val['actions']);
				if($roleHas && in_array($roleTop, $roleHas)){
					// 标示已获取的顶级权限，即已选中
					$rbacMenu .= '<label class="checkbox-inline"><input type="checkbox" class="rolecheck rbac-top" data-pid="' . $val['id'] . '" name="ids[]" value="' . $val['id'] . '" checked> ' . $val['name'] . '</label>';
				}else{
					$rbacMenu .= '<label class="checkbox-inline"><input type="checkbox" class="rolecheck rbac-top" data-pid="' . $val['id'] . '" name="ids[]" value="' . $val['id'] . '"> ' . $val['name'] . '</label>';
				}
				$rbacMenu .= '</td></tr>';
				
				// 子菜单
				if($childMenu){
					$rbacMenu .= '<tr><td style="padding: 0px 50px;">';
					foreach($childMenu as $ckey => $cval){
						$roleChild = strtolower($cval['module'] . '/' . $cval['control'] . '/' . $cval['actions']);
						if($cval['pid'] == $val['id']){
							// 子菜单中的顶级菜单
							$rbacMenu .= '<div style="margin-left: -25px; color: red;">';
							if($roleHas && in_array($roleChild, $roleHas)){
								// 标示已获取的权限，即已选中
								$rbacMenu .= '<label class="checkbox-inline"><input type="checkbox" class="rolecheck rbac-top-' . $val['id'] . '" data-myid="' . $cval['id'] . '" name="ids[]" value="' . $cval['id'] . '" checked> ' . $cval['name'] . '</label>';
							}else{
								$rbacMenu .= '<label class="checkbox-inline"><input type="checkbox" class="rolecheck rbac-top-' . $val['id'] . '" data-myid="' . $cval['id'] . '" name="ids[]" value="' . $cval['id'] . '"> ' . $cval['name'] . '</label>';
							}
							$rbacMenu .= '</div>';
						}else{
							if($roleHas && in_array($roleChild, $roleHas)){
								// 标示已获取的权限，即已选中
								$rbacMenu .= '<label class="checkbox-inline"><input type="checkbox" class="rolecheck rbac-top-' . $val['id'] . ' firtop-' . $cval['mytopid'] . '" name="ids[]" value="' . $cval['id'] . '" checked> ' . $cval['name'] . '</label>';
							}else{
								$rbacMenu .= '<label class="checkbox-inline"><input type="checkbox" class="rolecheck rbac-top-' . $val['id'] . ' firtop-' . $cval['mytopid'] . '" name="ids[]" value="' . $cval['id'] . '"> ' . $cval['name'] . '</label>';
							}
						}
					}
					$rbacMenu .= '</td></tr>';
				}
			}

		}
		
		$this -> assign('rbacmenu', $rbacMenu);
		$this -> assign('role_id', $role_id);
		return view('authorize');
	}



	// 保存角色权限设置
	public function authorizesave() {
		
		$inputs = Request::post();
		
		$role_id = (int) $inputs['id'];
		$role_id = $role_id > 0 ? $role_id : 0;
		
		$ids = $inputs['ids']; // 数组
		
		if(!$role_id){
			return json(['status' => 0, 'msg' => '角色信息错误']);
		}
		
		// 判断，不能修改超级管理员的
		if($role_id == 1){
			return json(['status' => 0, 'msg' => '角色信息错误']);
		}
		
		// 处理 权限信息 id
		$newIds = array();
		if($ids){
			$newIds = $ids;
			
			if($newIds && is_array($newIds)){
				// in 方法查出 对应的菜单信息
				$where = [
					['id', 'in', $newIds]
				];
				$menuLists = model('Menu')->where($where)-> column('id,module,control,actions');
				
				if($menuLists){
					// 批量删除旧的权限信息
					model('Authaccess') -> where(array('role_id' => $role_id)) -> delete();
					
					// 组合权限信息
					$authArray = array();
					foreach($menuLists as $key => $val){
						$val['role_id'] = $role_id;
						$val['rule_name'] = strtolower($val['module'] . '/' . $val['control'] . '/' . $val['actions']);
						unset($val['module']);
						unset($val['control']);
						unset($val['actions']);
						unset($val['id']);
						
						$authArray[] = $val;
					}
					
					// 批量插入权限信息
					model('Authaccess') -> saveAll($authArray);
					if (Cache::get('rbacMenu')) {
						cache('rbacMenu', null);
					}
					return json(['status' => 1, 'msg' => '操作成功']);
				}
			}
			return json(['status' => 0, 'msg' => '权限信息错误']);
		}else{
			// 批量删除旧的权限信息
			model('Authaccess') -> where(array('role_id' => $role_id)) -> delete();
			
			return json(['status' => 1, 'msg' => '操作成功']);
		}
		
		return json(['status' => 0, 'msg' => '操作失败']);
	}



}