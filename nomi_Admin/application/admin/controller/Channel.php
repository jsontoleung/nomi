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

		if (!$this->isAccess()) return view('common/common');

		$list = self::$_channel->order('channel_id desc')->select();

		return view('channel/info/index', [
			'list' => $list,
		]);

	}



	/**
	 * 会员记录
	 */
	public function memberinfo($channel_id) {

		if (!$this->isAccess()) return view('common/common');

		$lists = model('record')->memberList($channel_id);

		return view('record/memberinfo', ['lists' => $lists, 'channel_id' => $channel_id]);		

	}



	/**
	 * 产品记录
	 */
	public function productInfo($channel_id) {

		if (!$this->isAccess()) return view('common/common');

		$lists = model('record')->productList($channel_id);

		return view('record/productinfo', ['lists' => $lists, 'channel_id' => $channel_id]);		

	}



	/**
	 * 渠道添加
	 */
	public function addInfo() {

		if (!$this->isAccess()) return view('common/common');

		return view('channel/info/add');

	}


	/**
	 * 渠道修改
	 */
	public function editInfo($id) {

		if (!$this->isAccess()) return view('common/common');

		$list = self::$_channel->where('channel_id=:id', ['id' => $id])->find();

		return view('channel/info/edit', ['list' => $list]);

	}


	/**
	 * 渠道保存save
	 */
	public function saveInfo() {

		if (!$this->isAccess()) return view('common/common');

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

		if (!$this->isAccess()) return view('common/common');

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

		if (!$this->isAccess()) return view('common/common');

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

		if (!$this->isAccess()) return view('common/common');

		return view('channel/retail/add', ['id' => $id]);

	}



	/**
	 * 分销商修改
	 */
	public function editRetail($id) {

		if (!$this->isAccess()) return view('common/common');

		$list = model('ChannelRetail')->where('retail_id=:id', ['id' => $id])->find();

		return view('channel/retail/edit', ['list' => $list]);

	}



	/**
	 * 分销商保存save
	 */
	public function saveRetail() {

		if (!$this->isAccess()) return view('common/common');

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

		if (!$this->isAccess()) return view('common/common');

		$retail_id = Request::param('id');

		if (model('ChannelRetail')->where('retail_id=:id', ['id' => $retail_id])->delete()) {
				
			return json(['status'=>1, 'msg'=>'删除成功']);

		}
		return json(['status'=>0, 'msg'=>'删除失败']);
	}




	/**
	 * 生成二维码
	 */
	public function qrcode() {

		$channel_id = Request::post('channel_id');

		if (Request::isPost()) {

			$res = $this->getAccessToken(APPID,APPSECRET,'client_credential');
			if ($res == 'success') {
	            $token = Cache::get('wx_token');
	            $access_token = $token['access_token'];
	        }else{
	            return json(['status'=>0, 'data' => $res]);
	        }

	        if (empty($access_token)) {
	            return json(['status'=>0, 'data' => 'access_token为空，无法获取二维码']);
	        }

            $path = 'pages/login/login?channel_id='.$channel_id;

            $width = 430;

            $res2 = $this->getWXACodeUnlimit($access_token,$path,$width);

            // 删除原有的图片
			$photo = self::$_channel->where(['channel_id'=>$channel_id])->value('channel_qrcode');

			if (!empty($photo)) {

				if (substr($photo, 0, 4) != 'http') {

					$filePath = $photo;
					if (is_file($filePath)) {

						unlink($filePath);

					}

				}

			}

            $file = "uploads/qrcode/channel/".$channel_id. 'code-'. time().".jpg";

            file_put_contents('./'.$file,$res2);

            if (file_exists($file)) {

                $datas['channel_qrcode'] = $file;
                if (self::$_channel->save($datas, ['channel_id' => $channel_id])) {
                    
                    return json(['status'=>1, 'msg' => '操作成功']);

                }

            }
            return json(['status'=>0, 'msg' => '操作失败']);


	    }


	}

	
	// 发送access_token
    public function getAccessToken($appid,$secret,$grant_type){
        if (empty($appid)||empty($secret)||empty($grant_type)) {
            return '参数错误';
        }
             // https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=APPID&secret=APPSECRET
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type={$grant_type}&appid={$appid}&secret={$secret}";
        if (Cache::get('wx_token')) {
            $token = Cache::get('wx_token');
            return 'success';
        }
        $json = https_request($url);
        $data=json_decode($json,true);
        if (empty($data['access_token'])) {
            return $data;
        }
        Cache::set('wx_token',$data,3600);
        return 'success';
    }
    // 获取带参数的二维码
    // 获取小程序码，适用于需要的码数量极多的业务场景。通过该接口生成的小程序码，永久有效，数量暂无限制。
    public function getWXACodeUnlimit($access_token,$path='',$width=430){
        if (empty($access_token)||empty($path)) {
            return 'error';
        }
             // https://api.weixin.qq.com/cgi-bin/wxaapp/createwxaqrcode?access_token=ACCESS_TOKEN
        $url = "https://api.weixin.qq.com/cgi-bin/wxaapp/createwxaqrcode?access_token={$access_token}";
        $data = array();
        $data['path'] = $path;
        //最大32个可见字符，只支持数字，大小写英文以及部分特殊字符：!#$&'()*+,/:;=?@-._~，其它字符请自行编码为合法字符（因不支持%，中文无法使用 urlencode 处理，请使用其他编码方式）
        $data['width'] = $width;
        //二维码的宽度，默认为 430px
        $json = https_request($url,json_encode($data));
        return $json;
    }




}