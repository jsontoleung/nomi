<?php
namespace app\admin\controller;
use app\common\controller\Adminbase;
use think\facade\Request;
use think\facade\Cache;


/*
** 评论列表
*/

class Comment extends Adminbase {
	private static $_comment = null; // 数据表对象
	private static $_voice = null; // 数据表对象
	
	// 优先加载
	public function  initialize() {
		parent::initialize();
		self::$_comment = model('comment');
		self::$_voice = model('voice');
	}
	
	public function index() {

		$vid = Request::param('id');

		if (Cache::get('commentInfo')) {
			$lists = Cache::get('commentInfo');
		} else {
			$lists = self::$_comment->commentInfo($vid);
			Cache::set('commentInfo', $lists);
		}

		return view('index', [
			'lists' => $lists,
		]);
	}


	/**
	 * 下级评论	
	 */
	public function junior() {

		$comid = Request::param('id');
		if (Cache::get('juniorInfo')) {
			$lists = Cache::get('juniorInfo');
		} else {
			$lists = self::$_comment->juniorInfo($comid);
			Cache::set('juniorInfo', $lists);
		}

		return view('junior', [
			'lists' => $lists,
		]);

	}


	
}
