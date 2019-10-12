<?php
namespace app\admin\controller;
use app\common\controller\Adminbase;
use app\common\config\Categorys;
use think\facade\Request;
use think\facade\Cache;
use think\Db;
/*
** 用户积分等级设置 控制器
*/

class User extends Adminbase {
	private static $_user = null; // 数据表对象
	private static $_addr = null; // 数据表对象
	private static $_info = null; // 数据表对象

	// 优先加载
	public function  initialize() {
		parent::initialize();
		// 实例化数据表模型
		self::$_user = model('User');
		self::$_addr = model('UserAddr');
		self::$_info = model('UserInfo');
	}

	public function index () {

		$channel_id = Request::param('channel_id') ? Request::param('channel_id') : '';

		$lists = self::$_user->getUserInfo($channel_id);

		return view('index', [
			'channel_id' => $channel_id,
			'lists' => $lists,
		]);
	}


	/**
	 * 删除
	 */
	public function deletes () {

		$id = Request::post('id');
		$del = self::$_user->deletes($id);
		if ($del['status'] == 1) {
			return json(['status'=>1, 'msg'=>$del['msg']]);
		}

	}



	/**
	 * 修改
	 */
	public function edit () {

		$id = Request::param('id');

		$list = self::$_user->where('user_id=:id', ['id'=>$id])->find();

		$topLevel = self::$_user->field('openid, nickname')->where('user_id=:id', ['id' => $list['pid']])->find();

		if (empty($topLevel)) {
			$topLevel['openid'] = '';
		}
		
		// 来源渠道
		$list['channel'] = Categorys::categoryChannel($list['channel_id']);

		// 会员等级
		$list['level'] = Categorys::categoryLevel($list['level']);

		return view('edit', [
			'list' => $list,
			'topLevel' => $topLevel,
		]);
	}



	/**
	 * 添加
	 */
	public function add () {

		// 来源渠道
		$list['channel'] = Categorys::categoryChannel();
		// 会员等级
		$list['level'] = Categorys::categoryLevel();

		return view('add', [
			'list' => $list,
		]);
	}



	/**
	 * 保存添加/修改
	 */
	public function save () {

		if (Request::isPost()) {
			
			$inputs = Request::post();
			$headimg = Request::file('headimg');
			$save = self::$_user->userSave($inputs, $headimg);
			if ($save['status'] == 1) {
				return json(['status'=>1, 'msg'=>$save['msg']]);
			}
			return json(['status'=>0, 'msg'=>$save['msg']]);

		}

	}



	/**
	 * 收货地址
	 */
	public function addrIndex() {

		$uid = Request::param('id');

		if (Cache::get('addrInfo')) {
			$lists = Cache::get('addrInfo');
		} else {
			$lists = self::$_addr->addrInfo($uid);
			Cache::set('addrInfo', $lists);
		}
		

		return view('user/addr/index', [
			'uid' => $uid,
			'lists' => $lists,
		]);
	}



	/**
	 * 删除收货地址
	 */
	public function addrDel() {

		if (Request::isPost()) {
			$id = Request::post('id');
			if (self::$_addr->where('addr_id=:id', ['id' => $id])->delete()) {
				return json(['status'=>1, 'msg'=>'删除成功']);
			}
		}

	}



	/**
	 * 添加收货地址
	 */
	public function addrAdd(){

		$uid = Request::param('uid');

		$place['province'] = Categorys::categoryProvince();

		if (Request::isPost()) {
			
			$data = Request::post();

			if ($data['price'] == 1) {
				$place['city'] = Categorys::categoryCity($data['pid']);
			} elseif($data['price'] == 2) {
				$place['area'] = Categorys::categoryArea($data['pid']);
			}
			return json(['status'=>1, 'msg'=>$place]);

		}

		return view('user/addr/add', [
			'uid' => $uid,
			'place' => $place,
		]);
	}



	/**
	 * 修改收货地址
	 */
	public function addrEdit() {

		$id = Request::param('id');

		$list = self::$_addr->where('addr_id=:id', ['id' => $id])->find();

		$place['province'] = Categorys::categoryProvince($list['province']);
		$place['city'] = Categorys::categoryCity($list['province'], $list['city']);
		$place['area'] = Categorys::categoryArea($list['city'], $list['area']);
		
		if (Request::isPost()) {

			$data = Request::post();

			if ($data['price'] == 1) {
				$place['city'] = Categorys::categoryCity($data['pid']);
			} elseif($data['price'] == 2) {
				$place['area'] = Categorys::categoryArea($data['pid']);
			}
			return json(['status'=>1, 'msg'=>$place]);
			
		}

		return view('user/addr/edit', [
			'id' => $id,
			'list' => $list,
			'place' => $place,
		]);
	}



	/**
	 * 保存收货地址
	 */
	public function saveAddr() {

		if (Request::isPost()) {
			
			$data = Request::post();
			$data['province'] = $data['provinceid'];
			$data['city'] = $data['cityid'];
			$data['area'] = $data['areaid'];
			$data['update_time'] = time();
			unset($data['provinceid']);
			unset($data['cityid']);
			unset($data['areaid']);
			
			if (empty($data['addr_id'])) {
				
				if (self::$_addr->data($data)->save()) {
					return json(['status'=>1, 'msg'=>'添加成功']);
				}
				return json(['status'=>0, 'msg'=>'添加失败']);

			} else {
				if (self::$_addr->save($data, ['addr_id'=>$data['addr_id']])) {
					return json(['status'=>1, 'msg'=>'编辑成功']);
				}
				return json(['status'=>0, 'msg'=>'编辑失败']);
			}

		}

	}



	/**
	 * 个人信息
	 */
	public function personalIndex($id) {

		$list = self::$_info->where('uid=:id', ['id' => $id])->find();
		if (!empty($list)) {
			
			$list['birth'] = date('Y-m-d', $list['birth']);
			$place['province'] = Categorys::categoryProvince($list['province']);
			$place['city'] = Categorys::categoryCity($list['province'], $list['city']);
			$place['area'] = Categorys::categoryArea($list['city'], $list['area']);
		} else {
			$list['birth'] = '';
			$list['sex'] = '';
			$list['phone'] = '';
			$list['age'] = '';
			$list['industry'] = '';
			$list['position'] = '';
			$list['hobby'] = '';
			$list['address'] = '';
			$place['province'] = '';
			$place['city'] = '';
			$place['area'] = '';
		}
		
		if (Request::isPost()) {

			$data = Request::post();

			if ($data['price'] == 1) {
				$place['city'] = Categorys::categoryCity($data['pid']);
			} elseif($data['price'] == 2) {
				$place['area'] = Categorys::categoryArea($data['pid']);
			}
			return json(['status'=>1, 'msg'=>$place]);
			
		}

		return view('user/info/index', [
			'id' => $id,
			'list' => $list,
			'place' => $place,
		]);

	}



	/**
	 * 保存个人信息
	 */
	public function personalSave() {

		if (Request::isPost()) {

			$data = Request::post();

			$save = self::$_info->personalSave($data);
			if ($save['status'] == 1) {
				return json(['status'=>1, 'msg'=>$save['msg']]);
			}
			return json(['status'=>0, 'msg'=>$save['msg']]);

		}

	}




}