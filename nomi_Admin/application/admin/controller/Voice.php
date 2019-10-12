<?php
namespace app\admin\controller;
use app\common\controller\Adminbase;
use app\common\config\Categorys;
use app\common\config\Upload;
use think\facade\Request;
use think\facade\Cache;
use think\facade\Env;
use think\File;

/*
** 音频列表
*/

class Voice extends Adminbase {
	private static $_voice = null; // 数据表对象
	private static $_detail = null; // 数据表对象
	
	// 优先加载
	public function  initialize() {
		parent::initialize();
		self::$_voice = model('voice');
		self::$_detail = model('VoiceDetail');
	}
	
	public function index() {

		if (Cache::get('voiceInfo')) {
			$lists = Cache::get('voiceInfo');
		} else {
			$lists = self::$_voice->voiceInfo();
			Cache::set('voiceInfo', $lists);
		}

		return view('index', [
			'lists' => $lists,
		]);
	}


	// 删除
	public function deletes () {

		$id = Request::param('id');
		if (self::$_voice->where('voice_id=:id', ['id' => $id])->delete()) {
			if (Cache::get('voiceInfo')) {
				Cache::set('voiceInfo', null);
				return json(['status' => 1, 'msg' => '删除成功']);
			}
		}
	}


	// 添加
	public function add () {
		$byPro = Categorys::proLists();
		$cate = Categorys::categoryLists();
		$type = Categorys::voiceType();
		
		return view('add', [
			'byPro' => $byPro,
			'cate' => $cate,
			'type' => $type,
		]);
	}


	// 修改
	public function edit () {

		$id = Request::param('id');
		$lists = self::$_voice->where('voice_id=:id', ['id'=>$id])->find();
		$byPro = Categorys::proLists($lists['proid']);
		$cate = Categorys::categoryLists($lists['cid']);
		$type = Categorys::voiceType($lists['voice_id']);

		return view('edit', [
			'lists' => $lists,
			'byPro' => $byPro,
			'cate' => $cate,
			'type' => $type,
		]);
	}


	// 保存添加、修改
	public function save () {

		if (Request::isPost()) {
			
			$inputs = Request::post();
			$cover = Request::file('cover');
			$cover_detail = Request::file('cover_detail');
			
			$save = self::$_voice->voiceSave($inputs, $cover, $cover_detail);
			if ($save['status'] == 1) {
				Cache::set('voiceInfo', null);
				return json(['status'=>1, 'msg' => $save['msg']]);
			}
			return json(['status'=>0, 'msg' => $save['msg']]);
			
		}

	}


	/**
	 * 内容详细
	 */
	public function content () {

		$id = Request::param('id');

		$list = self::$_voice->where('voice_id=:id', ['id' => $id])->value('content');
		
		
		return view('content', [
			'list' => $list
		]);
	}


	/**
	 * 详情列表
	 */
	public function detail () {

		$id = Request::param('id');
		$type = Request::param('type');
		$lists = self::$_detail->detailInfo($id);

		return view('voice/detail/index', [
			'lists' => $lists,
			'type' => $type,
			'vid' => $id,
		]);
	}


	/**
	 * 删除详情
	 */
	public function detailDel () {

		$id = Request::param('id');

	}


	/**
	 * 添加详情
	 */
	public function detailAdd () {
		$id = Request::param('id');
		$type = Request::param('type');

		return view('voice/detail/add', [
			'vid' => $id,
			'type' => $type,
		]);
	}


	/**
	 * 修改详情
	 */
	public function detailEdit () {

		$id = Request::param('id');
		$type = Request::param('type');

		$list = self::$_detail->where('detail_id=:id', ['id' => $id])->find();


		return view('voice/detail/edit', [
			'type' => $type,
			'list' => $list,
		]);
	}


	/**
	 * 保存添加、修改详情
	 */
	public function detailSave () {

		if (Request::isPost()) {
			
			$inputs = Request::post();
			$cover = Request::file('cover');
			$video = Request::file('video');
			
			$save = self::$_detail->detailSave($inputs, $cover, $video);
			if ($save['status'] == 1) {
				return json(['status'=>1, 'msg' => $save['msg']]);
			}
			return json(['status'=>0, 'msg' => $save['msg']]);
			
		}

		
	}


	/**
	 * 文章详情内容详细
	 */
	public function detailcontent () {

		$id = Request::param('id');
		$type = Request::param('type');

		if ($type == 1) {
			$list = self::$_detail->where('detail_id=:id', ['id' => $id])->value('content');
		} else {
			$list = self::$_detail->where('detail_id=:id', ['id' => $id])->value('video');
		}
		
		return view('voice/detail/detailcontent', [
			'type' => $type,
			'list' => $list
		]);
	}



	
}
