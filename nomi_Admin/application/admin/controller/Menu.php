<?php
namespace app\admin\controller;
use app\common\controller\Adminbase;
use think\facade\Request;
use think\facade\Cache;
use think\Db;

/*
** Menu 控制器
*/

class Menu extends Adminbase {
	private static $_menu = null; // 数据表对象

	// 优先加载
	public function  initialize() {
		parent::initialize();
		// 实例化数据表模型
		self::$_menu = model('Menu');
	}


	// 菜单列表
	public function index() {
		
		if (Cache::get('menuInfo')) {
			$lists = Cache::get('menuInfo');
		} else {
			$lists = self::$_menu->menuLists();
			Cache::set('menuInfo', $lists);
		}
		
		$this->assign('menulists', $lists);
		return view('index');
	}


	// 新增菜单页面
	public function add() {
		
		$lists = self::$_menu->menuLists();
		
		$this->assign('menulists', $lists);
		return view('add');
	}


	// 编辑菜单页面
	public function edit() {
		
		$id = input('param.id/d');
		if(!$id){
			$this->error('参数错误');
		}
		
		$lists = self::$_menu ->getMenuDetail($id);
		if(!$lists){
			$this->error('参数错误');
		}

		$list = self::$_menu->menuLists();
		
		$this->assign('menulists', $list);
		$this->assign('lists', $lists);
		return view('edit');
	}


	// 添加、更改
	public function save () {

		if (Request::isPost()) {
			
			$inputs = Request::post();
			if (empty($inputs['id'])) {

				// 使用模型验证器进行验证
				$result = $this->validate($inputs, 'Menu.add');
				if(true !== $result){
					// 验证失败 输出错误信息
					$this -> error($result);
				}
				
				// 验证新增数据是否重复
				$this -> checkActionAdd($inputs);
				
				// 保存数据
				if(self::$_menu->data($inputs)->save()){
					if (Cache::get('menuInfo')) {
						Cache::set('menuInfo', null);
					}
					return json(['status'=>1, 'msg'=>'操作成功']);
				}

			} else {

				// 使用模型验证器进行验证
				$result = $this->validate($inputs, 'Menu.edit');
				if(true !== $result){
					// 验证失败 输出错误信息
					return json(['status'=>0, 'msg'=>$result]);
				}
				
				$find = self::$_menu->where(array('id'=>$inputs['id'])) -> value('id');
				if(!$find){
					return json(['status'=>0, 'msg'=>'参数错误']);
				}
				
				// 验证更新数据是否重复
				$this->checkActionUpdate($inputs);

				//获取传入的数据
				// 处理 path、topid
				$id = $inputs['id'];
				$pid = $inputs['pid'];
				if($pid > 0){
					// 获取上级菜单的 path
					$prentPath = self::$_menu->where(array('id' => $pid))->value('path');
					
					$pPath = explode('-', $prentPath);
					
					$inputs['path'] = $prentPath . '-' . $id;
					// 0-1 中 1 的位置，即本系列首个菜单
					$inputs['topid'] = $pPath[1];

				}else{
					$inputs['path']  = '0-' . $id;
					$inputs['topid'] = 0;
				}
				
				
				// 使用模型功能保存数据，方便调用模型事件
				if(self::$_menu->allowField(true)->save($inputs, array('id'=>$id))){
					if (Cache::get('menuInfo')) {
						Cache::set('menuInfo', null);
					}
					return json(['status'=>1, 'msg'=>'操作成功']);
				}

			}

		}

	}


	// 删除菜单
	public function deletes() {
		
		if (Request::isPost()) {
			
			$id = Request::post('id');
			$result = self::$_menu -> deletes($id);
			if($result['status'] == 1){
				if (Cache::get('menuInfo')) {
					Cache::set('menuInfo', null);
				}
				return json(['status'=>1, 'msg'=>'删除成功']);
			}
			return json(['status'=>0, 'msg'=>$result['msg']]);
		}
		
	}


	// 验证新增数据 验证 module,control,actions 是否重复添加
	private function checkActionAdd($data) {
		$checkData = array(
				'module' 	=> $data['module'],
				'control' 	=> $data['control'],
				'actions' 	=> $data['actions']
			);
		$find = self::$_menu -> where($checkData) -> find();
		if($find){
			return json(['status'=>0, 'msg'=>'同样的记录已经存在']);
		}
		return true;
	}
	
	// 验证更新数据 验证 module,control,actions 是否重复添加
	private function checkActionUpdate($data) {
		
		$checkData = array(
				'module' 	=> $data['module'],
				'control' 	=> $data['control'],
				'actions' 	=> $data['actions']
			);
		$find = self::$_menu -> where($checkData) -> value('id');
		
		if($find && $find != $data['id']){
			return json(['status'=>0, 'msg'=>'同样的记录已经存在']);
		}
		return true;
	}


}