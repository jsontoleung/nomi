<?php
namespace app\admin\controller;
use app\common\controller\Adminbase;
use app\common\config\Categorys;
use think\facade\Request;
use think\facade\Cache;

/*
** 产品列表 控制器
*/

class Product extends Adminbase {
	private static $_pro = null; // 数据表对象
	private static $_procom = null; // 数据表对象

	// 优先加载
	public function  initialize() {
		parent::initialize();
		// 实例化数据表模型
		self::$_pro = model('product');
		self::$_procom = model('ProductComment');
	}


	public function index() {

		if (Cache::get('proInfo')) {
			$lists = Cache::get('proInfo');
		} else {
			$lists = self::$_pro->proInfo();
			Cache::set('proInfo', $lists);
		}

		return view('index', [
			'lists' => $lists
		]);
	}



	/**
	 * 状态
	 */
	public function proStatus() {

		$data = Request::param();
		$inputs['is_down'] = $data['is_down'] == 0 ? 1 : 0;
		$save = self::$_pro->save($inputs, ['pro_id' => $data['id']]);
		if ($save) {
			Cache::set('proInfo', null);
			Cache::set('serveInfo', null);
			return json(['status' => 1, 'msg' => '操作成功']);
		}
		return json(['status' => 0, 'msg' => '操作失败']);

	}



	/**
	 * 详细信息
	 */
	public function content() {

		$id = Request::param('id');
		$list = self::$_pro
				->where('pro_id=:id', ['id'=>$id])
				->where('type=:id', ['id' => 0])
				->order('update_time')
				->find();

		return view('content', [
			'list' => $list
		]);
	}



	/**
	 * 轮播图
	 */
	public function lunbo() {

		$pro_id = Request::param('id');

		$photo = self::$_pro->where('pro_id=:id', ['id'=>$pro_id])->value('photo_group');
		$arrs = explode(',', $photo);
		foreach ($arrs as $k => $v) {
			$lists[$k] = $v;
		}
		
		if (Request::isPost()) {
			
			$files = Request::param(true);

			$save = self::$_pro->proUpload($files);
			if ($save['status'] == 1) {
				$this->success('操作成功');
			}
			$this -> error('操作失败');
		}

		return view('lunbo', [
			'lists' => $lists,
			'pro_id' => $pro_id,
		]);

	}



	/**
	 * 添加商品库存
	 */
	public function addInv() {

		$id = Request::param('id');
		$current_cnt = model('WarehouseProduct')->where('proid=:id', ['id' => $id])->value('current_cnt');
		if (empty($current_cnt)) $current_cnt = 0;

		if (Request::isPost()) {
			
			$data = Request::post('');

			$have = model('WarehouseProduct')->where('proid=:id', ['id' => $data['proid']])->find();
			if (empty($have)) {
				$save = model('WarehouseProduct')->data($data)->save();
			} else {
				$save = model('WarehouseProduct')->save($data, ['wp_id' => $have['wp_id']]);
			}
			if ($save) {
				Cache::set('proInfo', null);
				return json(['status' => 1, 'msg' => '操作成功']);
			}
			return json(['status' => 0, 'msg' => '操作失败']);

		}

		return view('addinv', [
			'proid' => $id,
			'current_cnt' => $current_cnt,
		]);

	}



	/**
	 * 产品属性
	 */
	public function buff() {

		$proid = Request::param('id');

		$pro_buff = self::$_pro->where('pro_id=:id', ['id'=>$proid])->value('pro_buff');
		if (empty($pro_buff)) {
			$lists = array();
		} else {
			$lists = json_decode($pro_buff, JSON_UNESCAPED_UNICODE);
			foreach ($lists as $k => $v) {
				$lists[$k] = implode('|', $v);
			}
		}
		
		return view('product/buff/index', [
			'proid' => $proid,
			'lists' => $lists,
		]);
	}



	/**
	 * 添加产品属性
	 */
	public function buffadd() {

		$proid = Request::param('id');

		$spec = model('Setting')->where(['name'=>'PARAM_PRODUCT'])->value('extert');
		$lists = parse_config_attr($spec);

		$html = '<select name="param_type" class="form-control" style="width:100%;">';
		$html .= '<option value="0">--选择规格属性--</option>';
		foreach ($lists as $k => $v) {

			$html .= '<option value="' . $v . '" >' . $v . '</option>';
		}
		$html .= '</select>';


		if (Request::isPost()) {
			
			$data = Request::post();

			foreach ($data['param_name'] as $k => $v) {
				$datas[$data['param_type']][] = $v;
			}

			$pro_buff = self::$_pro->where('pro_id=:id', ['id'=>$data['pro_id']])->value('pro_buff');
			$data2 = json_decode($pro_buff,JSON_UNESCAPED_UNICODE);
			if (empty($data2)) {
				$inputs = $datas;
			} else {
				$inputs = array_merge($data2, $datas);	
			}
			// 将规格转换json格式
			$params = json_encode($inputs);

			// 保存
        	$result = self::$_pro->where(array('pro_id' => $data['pro_id']))->update(array('pro_buff'=>$params));
        	if ($result) {
        		return json(['status' => 1, 'msg' => '操作成功']);
        	}
        	return json(['status' => 0, 'msg' => '操作失败']);

		}


		return view('product/buff/add', [
			'proid' => $proid,
			'html' => $html,
		]);
	}



	/**
	 * 删除产品属性
	 */
	public function buffdeletes() {

		if (Request::isPost()) {
			
			$keys = Request::post('keys');
			$proid = Request::post('proid');

			$pro_buff = self::$_pro->where('pro_id=:id', ['id'=>$proid])->value('pro_buff');
			$lists = json_decode($pro_buff, JSON_UNESCAPED_UNICODE);
			foreach ($lists as $k => $v) {
				
				if ($k == $keys) {
					unset($lists[$k]);
				}

			}
			// 将规格转换json格式
			$params = json_encode($lists);
			// 保存
        	$result = self::$_pro->where(array('pro_id' => $proid))->update(array('pro_buff'=>$params));
        	if ($result) {
        		return json(['status' => 1, 'msg' => '删除成功']);
        	}
        	return json(['status' => 0, 'msg' => '删除失败']);

		}

	}




	/**
	 * 删除
	 */
	public function deletes() {

		$id = Request::param('id');
		if (self::$_pro->where('pro_id=:id', ['id'=>$id])->delete()) {
			Cache::set('proInfo', null);
			Cache::set('serveInfo', null);
			return json(['status' => 1, 'msg' => '删除成功']);
		}

	}



	/**
	 * 添加
	 */
	public function add() {

		// 会员等级
		$level = Categorys::categoryLevel();

		return view('add', [
			'level' => $level,
		]);
	}



	/**
	 * 修改
	 */
	public function edit() {

		$id = Request::param('id');
		$list = self::$_pro->where('pro_id=:id', ['id' => $id])->find();
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



	/**
	 * 保存添加、修改
	 */
	public function save() {

		if (Request::isPost()) {
			
			$data = Request::post();
			$photo = Request::file('photo');
			
			$save = self::$_pro->proSave($data, $photo);
			if ($save) {
				Cache::set('proInfo', null);
				Cache::set('serveInfo', null);
				return json(['status'=>1, 'msg' => '操作成功']);
			}
			return json(['status'=>0, 'msg' => '操作失败']);

		}

	}



	/**
	 * 产品评论列表
	 */
	public function commentInfo() {

		$proid = Request::param('id');

		if (Cache::get('commentInfo')) {
			$lists = Cache::get('commentInfo');
		} else {
			$lists = self::$_procom->commentInfo($proid);
			Cache::get('commentInfo', $lists, 3600);
		}

		return view('product/comment/index', [
			'lists' => $lists,
		]);
	}



	/**
	 * 删除产品评论
	 */
	public function comDeletes() {

		$comid = Request::param('id');
		$oneuid = self::$_procom->where('comment_id=:id', ['id'=>$comid])->value('uid');
		$junior = self::$_procom->where('p_uid=:id', ['id'=>$oneuid])->count();
		
		if ($junior > 0) {
			return json(['status' => 0, 'msg' => '下级还有评论，不能删除']);
		} else {
			if (self::$_procom->where('comment_id=:id', ['id'=>$comid])->delete()) {
				return json(['status' => 1, 'msg' => '删除成功']);
			}
			return json(['status' => 0, 'msg' => '删除失败']);
		}

	}



	/**
	 * 产品下级评论
	 */
	public function commenTwo() {

		$uid = Request::param('uid');
		$pro_id = Request::param('pro_id');
		$lists = self::$_procom->commenTwoInfo($uid, $pro_id);

		return view('product/comment/junior', [
			'lists' => $lists,
		]);
	}



	/**
	 * 删除下级评论
	 */
	public function juniorDeletes() {
		$comid = Request::param('id');
		if (self::$_procom->where('comment_id=:id', ['id'=>$comid])->delete()) {
			return json(['status' => 1, 'msg' => '删除成功']);
		}
		return json(['status' => 0, 'msg' => '删除失败']);
	}



}