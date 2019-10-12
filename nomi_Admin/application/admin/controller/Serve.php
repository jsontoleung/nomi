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

		// 会员等级
		$level = Categorys::categoryLevel();

		return view('add', [
			'level' => $level,
		]);
	}



	// 修改
	public function edit() {

		$id = Request::param('id');
		$list = model('Product')->where('pro_id=:id', ['id' => $id])->find();
		if ($list['end_time'] > 0) {
			$list['end_time'] = date('Y-m-d H:i', $list['end_time']);
		}

		// 会员等级
		$level = Categorys::categoryLevel($list['level']);

		return view('edit', [
			'list' => $list,
			'level' => $level,
		]);
	}





}