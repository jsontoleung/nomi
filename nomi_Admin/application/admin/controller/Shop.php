<?php
namespace app\admin\controller;
use app\common\controller\Adminbase;
use app\common\config\Categorys;
use think\facade\Request;
use think\facade\Cache;
use think\Db;
/*
** 店铺列表 控制器
*/

class Shop extends Adminbase {
	private static $_shop = null; // 数据表对象
	private static $_common = null; // 数据表对象

	// 优先加载
	public function  initialize() {
		parent::initialize();
		self::$_shop = model('Shop');
		self::$_common = model('Common');
	}


	public function index() {

		$list = self::$_shop->shopList();

		return view('index', ['list' => $list]);

	}


	// 删除店铺
	public function deletes() {

		if (!$this->isAccess()) return view('common/common');

		$shopid = Request::post('id');
		if (!empty($shopid)) {
			$del = self::$_shop->where('shop_id=:id', ['id' => $id])->delete();
			if ($del) {
				return json(['status' => 1, 'msg' => '删除成功']);
			}
			return json(['status' => 0, 'msg' => '删除失败']);
		}
		return json(['status' => 0, 'msg' => '删除失败']);

	}


	// 添加店铺
	public function add() {

		if (!$this->isAccess()) return view('common/common');

		// 店铺后台
		$admins = Categorys::categoryAdmins();

		// 所属渠道
		$channel = Categorys::categoryChannel();

		// 所属省份
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

		return view('add', ['channel'=>$channel, 'admin'=>$admins, 'place'=>$place]);
	}


	// 修改店铺
	public function edit() {

		if (!$this->isAccess()) return view('common/common');
		
		$id = Request::param('id');
		$list = self::$_shop->where('shop_id=:id', ['id' => $id])->find();

		// 店铺后台
		$admins = Categorys::categoryAdmins($list['admin_id']);

		// 所属渠道
		$channel = Categorys::categoryChannel($list['channel_id']);

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

		return view('edit', ['list'=>$list, 'channel'=>$channel, 'admin'=>$admins, 'place'=>$place]);
	}


	// 添加、修改保存
	public function save() {

		if (!$this->isAccess()) return view('common/common');

		$data = Request::param();

		$data['province'] = $data['provinceid'];
		$data['city'] = $data['cityid'];
		$data['area'] = $data['areaid'];
		$data['add_time'] = time();
		unset($data['provinceid']);
		unset($data['cityid']);
		unset($data['areaid']);

		$list = self::$_shop->shopSave($data);

		return json(['status' => $list['status'], 'msg' => $list['msg']]);
	}



	// 旗下服务
	public function brands() {

		if (!$this->isAccess()) return view('common/common');

		$shopid = Request::param('id');

		$shop = self::$_shop->where('shop_id=:id', ['id' => $shopid])->find();

		$proid = json_decode($shop['pro_id'], JSON_UNESCAPED_UNICODE);

		$list = model('Product')->where(['type' => 1])->select();
		$serveList = '';
		$serveList .= '<tr><td>';
		foreach ($list as $key => $val) {

			if (is_array($proid) && in_array($val['pro_id'], $proid)) {
				$serveList .= '<label class="checkbox-inline"><input type="checkbox" class="rolecheck rbac-top-' . $val['pro_id'] . '" data-myid="' . $val['pro_id'] . '" name="ids[]" value="' . $val['pro_id'] . '" checked> ' . $val['name'] . '</label>';
			} else {
				$serveList .= '<label class="checkbox-inline"><input type="checkbox" class="rolecheck rbac-top-' . $val['pro_id'] . '" data-myid="' . $val['pro_id'] . '" name="ids[]" value="' . $val['pro_id'] . '"> ' . $val['name'] . '</label>';
			}

		}
		$serveList .= '</td></tr>';


		return view('brands', ['serveList'=>$serveList, 'shopid'=>$shopid]);

	}



	// 保存旗下服务
	public function authorizesave() {

		if (!$this->isAccess()) return view('common/common');
		
		$inputs = Request::post();
		
		$shop_id = (int) $inputs['shop_id'];
		$shop_id = $shop_id > 0 ? $shop_id : 0;
		
		$ids = $inputs['ids']; // 数组

		$proid = json_encode($ids);

		$save = self::$_shop->where('shop_id=:id', ['id' => $shop_id])->update(array('pro_id' => $proid));
		
		if ($save) {
			return json(['status'=>1, 'msg'=>'操作成功']);
		}
			return json(['status'=>0, 'msg'=>'操作失败']);
		
		
	}




}