<?php
namespace app\admin\controller;
use app\common\controller\Adminbase;
use app\common\config\Categorys;
use think\facade\Request;
use think\facade\Cache;
use think\Db;

/*
** 企业体系 控制器
*/

class Channel extends Adminbase {
	private static $_channel = null; // 数据表对象

	// 优先加载
	public function  initialize() {
		parent::initialize();
		// 实例化数据表模型
		self::$_channel = model('Channel');
	}



	/**
	 * 渠道列表
	 */
	public function info() {

		$list = self::$_channel->order('channel_id desc')->select();

		return view('channel/info/index', [
			'list' => $list,
		]);

	}



	/**
	 * 会员记录
	 */
	public function memberinfo($channel_id) {

		$lists = model('record')->memberList($channel_id);

		return view('record/memberinfo', ['lists' => $lists, 'channel_id' => $channel_id]);		

	}



	/**
	 * 产品记录
	 */
	public function productInfo($channel_id) {

		$lists = model('record')->productList($channel_id);

		return view('record/productinfo', ['lists' => $lists, 'channel_id' => $channel_id]);		

	}



	/**
	 * 渠道添加
	 */
	public function addInfo() {

		return view('channel/info/add');

	}


	/**
	 * 渠道修改
	 */
	public function editInfo($id) {

		$list = self::$_channel->where('channel_id=:id', ['id' => $id])->find();

		return view('channel/info/edit', ['list' => $list]);

	}


	/**
	 * 渠道保存save
	 */
	public function saveInfo() {

		$data = Request::post();
		$data['add_time'] = time();

		if (empty($data['channel_id'])) {

			Db::startTrans();
			try {

				$channelAdd = self::$_channel->data($data)->save();
				if ($channelAdd) {
					$chnnelInfo = self::$_channel->field('channel_id')->order('channel_id desc')->find();
					$data2['channel_id'] = $chnnelInfo['channel_id'];
					$retailAdd = model('ChannelRetail')->data($data2)->save();
					if ($retailAdd) {
						Db::commit();
						return json(['status'=>1, 'msg'=>'操作成功']);
					}
				}

				Db::rollback();
				return json(['status'=>0, 'msg'=>'操作失败']);
				
			} catch (Exception $e) {
				Db::rollback();
		       	throw $e;
			}


		} else {

			if (self::$_channel->save($data, ['channel_id' => $data['channel_id']])) {
				
				return json(['status'=>1, 'msg'=>'操作成功']);

			}
			return json(['status'=>0, 'msg'=>'操作失败']);

		}



	}



	/**
	 * 渠道删除
	 */
	public function delInfo() {

		$channel_id = Request::param('id');

		Db::startTrans();
		try {

			$delChannel = self::$_channel->where('channel_id=:id', ['id' => $channel_id])->delete();
			$delRetail = model('ChannelRetail')->where('channel_id=:id', ['id' => $channel_id])->delete();

			if ($delChannel && $delRetail) {
				Db::commit();
				return json(['status'=>1, 'msg'=>'删除成功']);
			}
			return json(['status'=>0, 'msg'=>'删除失败']);
				
		} catch (Exception $e) {
			Db::rollback();
		    throw $e;
		}

	}




	/**
	 * 分销商
	 */
	public function retail() {

		$channel_id = Request::param('channel_id');

		$list = model('ChannelRetail')
			->alias('cr')
			->field('cr.*, c.channel_name')
			->leftJoin('channel c', ['cr.channel_id = c.channel_id'])
			->where('cr.channel_id=:id', ['id' => $channel_id])
			->select();

		foreach ($list as $k => $v) {
			
			$list[$k]['count'] = model('MemberRetail')
				->alias('mr')
				->leftJoin('member_order mo', ['mr.order_id = mo.order_id'])
				->where(['mo.channel_id' => $v['channel_id']])
				->where(['mo.pay_status' => 1])
				->count();

		}

		return view('channel/retail/index', [
			'channel_id' => $channel_id,
			'list' => $list,
		]);

	}



	/**
	 * 分销商添加
	 */
	public function addRetail($id) {

		return view('channel/retail/add', ['id' => $id]);

	}



	/**
	 * 分销商修改
	 */
	public function editRetail($id) {

		$list = model('ChannelRetail')->where('retail_id=:id', ['id' => $id])->find();

		return view('channel/retail/edit', ['list' => $list]);

	}



	/**
	 * 分销商保存save
	 */
	public function saveRetail() {

		$data = Request::post();

		if (empty($data['retail_id'])) {

			if (model('ChannelRetail')->data($data)->save()) {
				
				return json(['status'=>1, 'msg'=>'操作成功']);

			}
			return json(['status'=>0, 'msg'=>'操作失败']);

		} else {

			if (model('ChannelRetail')->save($data, ['retail_id' => $data['retail_id']])) {
				
				return json(['status'=>1, 'msg'=>'操作成功']);

			}
			return json(['status'=>0, 'msg'=>'操作失败']);

		}

	}



	/**
	 * 分销商删除
	 */
	public function delRetail() {

		$retail_id = Request::param('id');

		if (model('ChannelRetail')->where('retail_id=:id', ['id' => $retail_id])->delete()) {
				
			return json(['status'=>1, 'msg'=>'删除成功']);

		}
		return json(['status'=>0, 'msg'=>'删除失败']);
	}





}