<?php
namespace app\admin\controller;
use app\common\controller\Adminbase;
use think\facade\Request;
use think\facade\Cache;
use think\Db;

/*
** Admin 控制器
*/

class Admin extends Adminbase {

	// 优先加载
	public function  initialize() {
		parent::initialize();
	}


	// 管理员列表
	public function index() {
		
		if (Cache::get('adminInfo')) {
			$lists = Cache::get('adminInfo');
		} else {
			$lists = model('admin') -> usersLists();
			Cache::set('adminInfo', $lists);
		}
		
		$this -> assign('adminInfo', $lists);
		return view('index');
	}



	// 新增管理员页面
	public function add() {

		$this -> assign('rolelists', $this -> getRoleList());
		return view('add');
	}



	// 编辑管理员页面
	public function edit() {
		
		$id = Request::param('id');
		if(!$id){
			$this->error('参数错误');
		}
		
		// 判断，不能修改 超级管理员
		if($id == 1){
			$this->error('角色信息错误');
		}
		
		$lists = model('admin')->getUsersDetail($id);
		if(!$lists){
			$this->error('参数错误');
		}
		
		$this -> assign('rolelists', $this->getRoleList($id));
		$this -> assign('lists', $lists);
		return view('edit');
	}


	// 添加、更改用户
	public function save () {

		$inputs = Request::post();

		if (Request::isPost()) {
			
			if (empty($inputs['id'])) {
				
				// 使用模型验证器进行验证
				$result = $this->validate($inputs, 'Admin.add');
				if(true !== $result){
					// 验证失败 输出错误信息
					return json(['status'=>0, 'msg'=>$result]);
				}
				
				// 验证 name 是否重复添加
				$this->checkNameAdd($inputs['name']);
				
				// 产生加密密钥
				$encrypt_salt = md5(random_string(20));
				
				// 处理生成密码、加密密钥
				$inputs['passwd'] = manager_password($inputs['passwd'], $encrypt_salt);
				$inputs['encrypt_salt'] = $encrypt_salt;
				
				// 处理时间
				$inputs['time_create'] = time();
				$inputs['time_update'] = time();

				// 服务后台类型
				if ($inputs['role_id'] == 5) {
					$inputs['type'] = 1;
				}
				
				// 验证角色 ID
				$this->checkRoleId($inputs['role_id']);
				
				// 保存数据
				$list = model('admin')->adminSave($inputs);
				if ($list) {
					if (Cache::get('adminInfo')) {
						Cache::set('adminInfo', null);
					}
					return json(['status'=>1, 'msg'=>'操作成功']);
				}

			} else {

				$id = (int) $inputs['id'];
				// 判断，不能修改 超级管理员
				if($id == 1){
					return json(['status'=>0, 'msg'=>'不能修改']);
				}

				$find = model('admin')->where(array('id' => $id))->value('id');
				if(!$find){
					return json(['status'=>0, 'msg'=>'参数错误']);
				}
				
				// 使用模型验证器进行验证
				$result = $this->validate($inputs, 'Admin.edit');
				if(true !== $result){
					// 验证失败 输出错误信息
					return json(['status'=>0, 'msg'=>$result]);
				}
				
				// 验证 name 是否重复添加
				$this->checkNameUpdate($inputs);

				// 处理密码
				if(!empty($inputs['passwd'])){
					// 产生加密密钥
					$encrypt_salt = md5(random_string(20));
					
					// 处理生成密码、加密密钥
					$inputs['passwd'] = manager_password($inputs['passwd'], $encrypt_salt);
					$inputs['encrypt_salt'] = $encrypt_salt;
				}

				// 服务后台类型
				if ($inputs['role_id'] == 5) {
					$inputs['type'] = 1;
				}

				// 处理时间
				$inputs['time_update'] = time();
				
				$role_id = $inputs['role_id'];
				// 验证角色 ID
				$this -> checkRoleId($role_id);

				// 保存数据
				$list = model('admin')->adminSave($inputs);
				if ($list) {
					if (Cache::get('adminInfo')) {
						Cache::set('adminInfo', null);
					}
					return json(['status'=>1, 'msg'=>'操作成功']);
				} else {
					return json(['status'=>0, 'msg'=>'操作失败']);
				}

			}

		}

	}


	// 删除用户
	public function deletes () {

		$id = Request::param('id');
		if (model('admin')->where(['id'=>$id])->delete()) {
			if (Cache::get('adminInfo')) {
				Cache::set('adminInfo', null);
			}
			return json(['status'=>1, 'msg'=>'操作成功']);
		}
		return json(['status'=>0, 'msg'=>'操作失败']);
	}



	// 角色选择
	private function getRoleList($uid = 0) {
		// 输出 有效 角色，不输出超级管理员
		$where = [
			['id', 'gt', 1]
		];
		$rolelists = model('Role')->where($where)->column('id, name, status');
		$role_id = '';
		if($uid){
			// 输出所在角色组
			$roleInfo = model('Roleuser')->where(array('uid' => $uid))->value('role_id');
			if($roleInfo){
				$role_id = $roleInfo;
			}
		}
		
		$roleBox = '';
		foreach($rolelists as $key => $val){
			// 处理权限状态，启用 / 禁用
			$status = '';
			// $color = '#333';
			if($val['status'] == 0){
				$status = 'disabled';
				// $color = '#CCC';
			}
			
			$roleBox .= '<label class="radio-inline">';
			if($role_id && ($role_id == $val['id'])){
				$roleBox .= '<input type="radio" name="role_id" value="' . $val['id'] . '" checked ' . $status . ' />' .  $val['name'];
			}else{
				$roleBox .= '<input type="radio" name="role_id" value="' . $val['id'] . '" ' . $status . ' />' .  $val['name'];
			}
			$roleBox .= '</label>';
		}
		return $roleBox;
	}


	// 验证 是否重复添加
	private function checkNameAdd($name) {
		// 传入为单个元素，则为字符串
		$find = model('admin') -> where(array('name' => $name)) -> find();
		if($find){
			return json(['status'=>0, 'msg'=>'同样的记录已经存在']);
		}
		return true;
	}
	
	// 验证是否重复添加
	private function checkNameUpdate($data) {
		
		$find = model('admin') -> where(array('name' => $data['name'])) -> value('id');
		
		if($find && $find != $data['id']){
			return json(['status'=>0, 'msg'=>'同样的记录已经存在']);
		}
		return true;
	}
	
	/*
	** 验证角色 ID
	*/
	private function checkRoleId($role_id) {
		if($role_id == 1){
			// 不能使用超级管理员
			return json(['status'=>0, 'msg'=>'请选择正确的角色']);
		}
		
		// 大于 1，验证是否存在、是否启用
		$where = array('id' => $role_id, 'status' => 1);
		$find = model('Role') -> where($where) -> find();
		if($find){
			return true;
		}
		return json(['status'=>0, 'msg'=>'请选择正确的角色']);
	}





}