<?php
namespace app\api\controller;
use think\Controller;
use think\facade\Request;
use wxchat\Wxchat;

class Login {
	

	public function getsessionkey() {

		$post = Request::param();
		if (Request::isPost()) {

			$param['appid'] = APPID;    //小程序id

			$param['secret'] = APPSECRET;    //小程序密钥

			if (!$post['code']) {
				echo json_encode(array('status' => 0,'err' => '非法操作！'));
				exit();
			}

			if (!$param['appid'] || !$param['secret']) {
				echo json_encode(array('status' => 0,'err' => '非法操作！'));
				exit();
			}

			$param['js_code'] = define_str_replace($post['code']);

			$param['grant_type'] = 'authorization_code';

			$http_key = httpCurl('https://api.weixin.qq.com/sns/jscode2session', $param, 'GET');

			$session_key = json_decode($http_key,true);

			if (empty($session_key)) {

				echo json_encode(array('status' => 0, 'msg' => '获取session_key失败！'));
				exit;
			} else {

				echo json_encode(array('status' => 1, 'res' => $session_key));
				exit;
			}

		} else {
			echo json_encode(array('status' => 0, 'msg' => '获取session_key失败！'));
			exit;
		}
	}


	public function wxlogin(){

		$post = Request::param();
		
		if (Request::isPost()) {

			$param['appid'] = 'wx1f5fdbdc76854173';    //小程序id

			if (!$param['appid']) {
				echo json_encode(array('status' => 0,'msg' => '非法操作！'));
				exit();
			}

			if (!empty($post['session_key'])) {

				$appid = $param['appid'];

				$encrypteData = urldecode($post['encrypteData']);

				$iv = define_str_replace($post['iv']);

				$errCode = decryptData($appid, $post['session_key'], $encrypteData, $iv);

				//把openid写入到数据库中
				$openid = $errCode['openId'];
				if (!$openid) {
					echo json_encode(array('status' => 0, 'msg'=> '授权失败！'));
					exit();
				}

				// 检测用户表是否存在该用户
				$res = model('user')->where(['openid' => trim($openid)])->find();
				
				if (empty($res['user_id'])) { //不存在就添加

					// 分享者uid
					$topuid = Request::param('topuid');
					if (!empty($topuid)) {
						$data['pid'] = $topuid;
					}

					// 添加用户注册信息
					$data = array();
					$data['openid'] = $openid;
					$data['accont'] = 'nomiya'.date('His').random_string(4);
					$data['nickname'] = emoji_encode($errCode['nickName']);
					$data['headimg'] = $post['headimg'];
					$data['register_type'] = 2;
					$data['register_time'] = time();
					$data['sex'] = $errCode['gender'];
					
					if (!$data['openid']) {
						echo json_encode(array('status' => 0, 'msg'=> '授权失败！'));
						exit();
					}

					$val = model('user')->data($data)->save();
					if ($val) {

						$value = array();
						$value['user_id'] = intval($val);
						$value['nickname'] = $data['nickname'];
						$value['headimg'] = $data['headimg'];
						$value['type'] = 0;
						session('user',$value);
						echo json_encode(array('status' => 1,'user' => $value));
						exit();

					}else{

						echo json_encode(array('status' => 0,'err'=>'授权失败！'));
						exit();

					}

				} else {

					// 检测用户是否存在
					$value = model('user')->where(['user_id' => intval($res['user_id'])])->find();
					if (intval($value['user_id']) == 0) {
						echo json_encode(array('status' => 0, 'msg' => '账号状态异常！'));
						exit();
					}

					// 分享者uid
					$topuid = Request::param('topuid');
					if (!empty($topuid)) {
						
						if ($value['pid'] == 0) {
							$data['pid'] = $topuid;
							model('user')->save($data, ['user_id' => $value['user_id']]);
						}
						
					}

					// 添加登陆日志
					$dataLog['uid'] = intval($value['user_id']);
					$dataLog['login_time'] = time();
					$dataLog['login_ip'] = get_ip();
					$dataLog['login_type'] = 1;
					$userLog = model('UserLoginLog')->data($dataLog)->save();
					if ($userLog) {
						
						// 返回用户信息
						$arr = array();
						$arr['user_id'] = intval($value['user_id']);
						$arr['openid'] = $value['openid'];
						$arr['accont'] = $value['accont'];
						$arr['nickname'] = $value['nickname'];
						$arr['headimg'] = $value['headimg'];
						$arr['type'] = 1;
						session('user',$value);
						

						echo json_encode(array('status' => 1,'user' => $arr));
						exit();

					}

				}
				session('openid', $openid);
				
			} else {
				echo json_encode(array('status' => 0, 'msg'=> '授权失败！'));
				exit();
			}
		} else {
			echo json_encode(array('status' => 0, 'msg' => '获取session_key失败！'));
			exit;
		}
	}
}