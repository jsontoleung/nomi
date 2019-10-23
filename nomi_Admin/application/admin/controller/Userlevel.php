<?php
namespace app\admin\controller;
use app\common\controller\Adminbase;
use think\facade\Request;
use think\facade\Cache;
/*
** 用户积分等级设置 控制器
*/

class Userlevel extends Adminbase {
	private static $_level = null; // 数据表对象

	// 优先加载
	public function  initialize() {
		parent::initialize();
		// 实例化数据表模型
		self::$_level = model('userlevel');


	}

	public function index () {

		if (Cache::get('userLevel')) {
			$lists = Cache::get('userLevel');
		} else {

			$lists = self::$_level->levelInfo();
			Cache::set('userLevel', $lists);
		}

		return view('index', [
			'lists' => $lists,
		]);
	}


	/**
	 * 删除
	 */
	public function deletes () {

		if (!$this->isAccess()) return view('common/common');

		if (Request::isPost()) {
			$id = Request::post('id');
			if(self::$_level->where(['level_id'=>$id])->delete()){
				if (Cache::get('userLevel')) {
					Cache::set('userLevel', null);
				}
				return json(['status'=>1, 'msg'=>'删除成功']);
			}
			return json(['status'=>0, 'msg'=>'删除失败']);
		}

	}



	/**
	 * 修改
	 */
	public function edit () {

		if (!$this->isAccess()) return view('common/common');

		$id = Request::param('id');
		$list = self::$_level->where('level_id=:id', ['id'=>$id])->find();

		return view('edit', [
			'list' => $list,
		]);
	}



	/**
	 * 添加
	 */
	public function add () {

		if (!$this->isAccess()) return view('common/common');

		return view();
	}



	/**
	 * 保存添加/修改
	 */
	public function save () {

		if (!$this->isAccess()) return view('common/common');

		if (Request::isPost()) {
			
			$inputs = Request::post();
			$save = self::$_level->levelSave($inputs);
			if ($save['status'] == 1) {
				Cache::set('userLevel', null);
				return json(['status'=>1, 'msg' => $save['msg']]);
			}
				return json(['status'=>0, 'msg' => $save['msg']]);

		}

	}




	/**
	 * 会员特权
	 */
	public function privilege() {

		if (!$this->isAccess()) return view('common/common');

		$id = Request::param('id');
		$list = model('UserPrivilege')
			->alias('up')
			->field('up.*, l.level_type')
			->leftJoin('user_level l', ['up.level_id = l.level_id'])
			->where('up.level_id=:id', ['id' => $id])
			->select();
		
		

		return view('privilege', [
			'level_id' => $id,
			'list' => $list,
		]);

	}




	/**
	 * 会员特权设置
	 */
	public function addPrivilege() {

		if (!$this->isAccess()) return view('common/common');

		$level_id = Request::param('level_id');

		if (Request::isPost()) {
			
			$data = Request::post();

			
			for ($i = 0; $i < count($data['name']); $i++) {

				$inputs[$i] = array(
					'level_id' 	=> $data['level_id'],
					'name' 		=> $data['name'][$i],
					'content' 	=> $data['content'][$i]
				);

			}

			foreach ($inputs as $kk => $vv) {

				$add = model('UserPrivilege')->insert($vv);
			}

			if ($add) {
				return json(['status'=>1, 'msg' => '操作成功!']);
			}

		}


		return view('add_privilege', [
			'level_id' => $level_id,
		]);
	}



	/**
	 * 编辑会员特权
	 */
	public function editPrivilege() {

		if (!$this->isAccess()) return view('common/common');

		$id = Request::param('id');
		$list = model('UserPrivilege')->where('id=:id', ['id' => $id])->select();

		if (Request::isPost()) {
			
			$data = Request::post();
			if (model('UserPrivilege')->save($data, ['id'=>$data['id']])) {
				return json(['status'=>1, 'msg' => '操作成功!']);
			}

		}

		return view('edit_privilege', [
			'list' => $list,
		]);
	}



	/**
	 * 删除会员特权
	 */
	public function delege() {

		if (!$this->isAccess()) return view('common/common');

		if (Request::isPost()) {
			$id = Request::post('id');
			if(model('UserPrivilege')->where(['id'=>$id])->delete()){
				return json(['status'=>1, 'msg'=>'删除成功']);
			}
			return json(['status'=>0, 'msg'=>'删除失败']);
		}

	}


}