<?php
namespace app\admin\controller;
use app\common\controller\Adminbase;
use app\common\config\Categorys;
use think\facade\Request;
use think\facade\Cache;

/*
** 产品服务列表 控制器
*/

class Serve extends Adminbase {
	private static $_serve = null; // 数据表对象

	// 优先加载
	public function  initialize() {
		parent::initialize();
		// 实例化数据表模型
		self::$_serve = model('Serve');
	}


	public function index() {

		if (Cache::get('serveInfo')) {
			$lists = Cache::get('serveInfo');
		} else {
			$lists = self::$_serve->serveInfo();
			Cache::set('serveInfo', $lists);
		}

		return view('index', [
			'lists' => $lists
		]);
	}



	// 添加
	public function add() {

		if (!$this->isAccess()) return view('common/common');

		//所属分类
		$category = Categorys::categoryLists();

		// 会员等级
		$level = Categorys::categoryLevel();

		return view('add', [
			'category' => $category,
			'level' => $level,
		]);
	}



	// 修改
	public function edit() {

		if (!$this->isAccess()) return view('common/common');

		$id = Request::param('id');
		$list = model('Product')->where('pro_id=:id', ['id' => $id])->find();
		if ($list['end_time'] > 0) {
			$list['end_time'] = date('Y-m-d H:i', $list['end_time']);
		}

		//所属分类
		$category = Categorys::categoryLists($list['cid']);

		// 会员等级
		$level = Categorys::categoryLevel($list['level']);

		return view('edit', [
			'list' => $list,
			'category' => $category,
			'level' => $level,
		]);
	}



	/**
	 * Banner轮播
	 */
	public function Banner() {

		if (!$this->isAccess()) return view('common/common');

		if (Cache::get('bannerInfo')) {
			$lists = Cache::get('bannerInfo');
		} else {
			$lists = model('Banner')->bannerInfo();
			Cache::set('bannerInfo', $lists);
		}

		return view('serve/banner/index', [
			'lists' => $lists,
		]);

	}

	/**
	 * 删除banner
	 */
	public function delBanner() {

		if (!$this->isAccess()) return view('common/common');

		if (Request::isPost()) {
			$id = Request::param('id');
			$del = model('Banner')->delBanner($id);
			if ($del) {
				Cache::set('bannerInfo', null);
				return json(['status'=>1, 'msg' => $del['msg']]);
			}
				return json(['status'=>0, 'msg' => $del['msg']]);
		}

	}

	/**
	 * 添加banner
	 */
	public function addBanner() {

		if (!$this->isAccess()) return view('common/common');

		//所属分类
		$category = Categorys::categoryLists();

		$byPro = Categorys::proLists();

		return view('serve/banner/add', [
			'category' => $category,
			'byPro' => $byPro,
		]);

	}

	/**
	 * 修改banner
	 */
	public function editBanner($id) {

		if (!$this->isAccess()) return view('common/common');

		$list = model('Banner')->where('banner_id=:id', ['id' => $id])->find();

		//所属分类
		$category = Categorys::categoryLists($list['cid']);

		$byPro = Categorys::proLists($list['proid']);

		return view('serve/banner/edit', [
			'list' => $list,
			'category' => $category,
			'byPro' => $byPro,
		]);

	}

	/**
	 * 修改Banner封面
	 */
	public function saveBanner() {

		if (!$this->isAccess()) return view('common/common');

		if (Request::isPost()) {
			
			$data = Request::param();
			$banner_img = Request::file('banner_img');
			
			$save = model('Banner')->saveBanner($data, $banner_img);
			if ($save) {
				Cache::set('bannerInfo', null);
				return json(['status'=>1, 'msg' => $save['msg']]);
			}
			return json(['status'=>0, 'msg' => $save['msg']]);

		}

	}





}