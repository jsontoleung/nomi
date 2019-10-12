<?php
namespace app\admin\controller;
use app\common\controller\Appbase;
use think\facade\Request;
use think\Db;


/*
** 登陆 控制器
*/

class Login extends Appbase {

	// 优先加载
	public function  initialize() {
		parent::initialize();
	}

	public function index () {
		
		return view('index');

	}


	// 注销登陆
	public function logout() {
		
		if (Request::isPost()) {

			$uid = session('uid');
			session(null);
			return json(['status'=>1, 'msg'=>'注销成功']);
		}
		return json(['status'=>0, 'msg'=>'小鬼这样不行哟']);
		
	}


	// 登陆接口
	public function toLogin () {

		if (Request::isPost()) {
			
			$inputs = Request::post();

			// 检测数据
			$this->checkDatas($inputs);
			
			$name = $inputs['name'];
			$pass = $inputs['passwd'];

			// 检测用户信息
			$result = model('admin')->where('name=:name', ['name' => $name])->find();
			if(!$result){
				return json(['status' => 2, 'msg' => '小鬼!换个账号吧']);
			}

			// 检测密码
			$passwd = manager_password($pass, $result['encrypt_salt']);
			if($passwd != $result['passwd']){
				return json(['status' => 2, 'msg' => '小鬼！换个密码吧']);
			}
			
			// 检测用户状态，0 禁用 1 正常
			if($result['status'] == 0){
				return json(['status' => 2, 'msg' => '小鬼！账号被拉黑啦？']);
			}
			
			// 检查用户的合法性
			$managerLawful = check_manager_lawful($result['id']);
			if(!$managerLawful){
				return json(['status' => 2, 'msg' => '小鬼！账号中毒了']);
			}

			// 登入成功，把管理员信息放入 session里，并页面跳转
			session('uid', $result['id']);
			session('role_id', $managerLawful['role_id']);
			session('username', $result['name']);
			session('nickname', $result['nickname']);
			session('headimg', $result['headimg']);
			session('login_ip', $result['login_ip']);
			session('login_time', $result['time_login']);
			session('login_count', $result['login_count'] + 1);
			session('last_time', time() + 3600 * 12);

			// 更新管理员登陆信息
			$data['login_ip'] 		= sprintf("%u\n", ip2long(Request::ip()));
			$data['time_login'] 	= time();
			$data['login_count'] 	= $result['login_count'] + 1;
			$save = model('admin')->where('id=:id', ['id' => $result['id']])->update($data);
			if ($save) {
				return json(['status' => 1, 'msg' => '欢迎'.$result['nickname']]);
			}

		}

	}


	// 检测数据
	private function checkDatas($datas) {
		$name = $datas['name'];
		$pass = $datas['passwd'];
		
		if(!$name){
			$this -> error('用户名不能为空');
		}
		
		if(!$pass){
			$this -> error('密码不能为空');
		}
	}




}