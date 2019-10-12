<?php
namespace app\common\controller;
use think\Controller;
use think\facade\Request;
use think\facade\Cache;
use think\Db;

Class Adminbase extends Controller {

	private static $_uid 		= 0; 	// 当前登陆的管理员 id
	private static $_role_id 	= 0; 	// 当前登陆的管理员角色组 id

	/**
	 * 优先加载
	 */
	public function initialize() {
		
		// 验证是否登陆登陆
		self::$_uid 	= session('uid');
		self::$_role_id = session('role_id');

		if (self::$_uid && self::$_role_id) {
			// 正确，验证权限组是否变更
			if (self::$_uid == 1) {
				// 超级管理员 跳过
			} else {

				// 验证权限组是否变更
				$role_id = Db::name('roleuser')->where('uid=:uid', ['uid'=>self::$_uid])->value('role_id');
				if($role_id == self::$_role_id){
					// 正确
				}else{
					// 注销，重新登陆
					session(null);
					header('Location:' . url('login/index'));
					exit();
				}

			}

		} else {

			// 未登陆，跳转到登陆页
			session(null);
			header('Location:' . url('login/index'));
			exit();

		}

		$lastime = session('last_time');
		if (!empty($lastime)) {
			if (($lastime - time()) <= 0) {
			    session(null);
			    $this->error('登录超时，请重新登录!', 'admin/login/index');
			}
		}

		// 检查管理员用户的合法性
		$managerLawful = check_manager_lawful(self::$_uid);
		
		if(!$managerLawful){

			session(null);
			header('Location:' . url('login/index'));
			exit();
			
		}

		// 即时检查当前管理员是否具有当前模块、控制器、方法的权限
		if($managerLawful['role_id'] == 1){
			// 如果是超级管理员或超级管理员组，直接通过
		}else{
			if(!auth_check(self::$_uid, $managerLawful['role_access'])){
				// if(is_ajax() && is_post()){
				// 	// ajax 的 post 方式
				// 	$this -> error('您的权限不足！');
				// }
				// $this->error('小鬼！您的权限不足！');

			}
		}
		
		// 检测站点是否已关闭
		$this->isOpen();

		$icon = model('setting')->field('values')->where(['name'=>'ADMIN_ICN'])->find();
		$adminName = model('setting')->field('values')->where(['name'=>'ADMIN_NAME'])->find();
		
		$showDatas = array(
				// 输出左侧菜单
				'sidebar' 		=> $this->sidebar(),
				// 输出当前菜单 / 当前位置
				'nowmenuname' 	=> $this->getNowMenuName('name'),
				// 昵称
				'nickname' 		=> session('nickname'),
				// 后台图标ICON
				'icon' 			=> $icon['values'],
				// 用户名
				'name' 			=> session('username'), 
				// 头像
				'headimg' 		=> session('headimg'),
				// 后台名称
				'adminName' 	=> $adminName['values'],
				// 输出系统时间
				'systime' 		=> time(),
			);
		
		$this -> assign($showDatas);
	}


	/**
	 * 左侧菜单
	 */
	private function sidebar () {

		$lists = model('Menu') -> sidebar();
		if (!$lists) {
			session(null);
			$this->error('请检查权限!', 'admin/login/index');
		}
		$html = '';
		foreach ($lists as $keyTop => $valTop) {
			
			$html .= '<li>';
	            $html .= '<a href="javascript:void(0)"><i class="fa fa-desktop"></i> <span class="nav-label">'. $valTop['name'] .'</span><span class="fa arrow"></span></a>';
	            if (isset($valTop['child']) && $valTop['child']) {
	            	// 处理一级目录
					foreach($valTop['child'] as $keyFir => $valFir){
						if(isset($valFir['child']) && $valFir['child']){
				            $html .= '<ul class="nav nav-second-level">';
				                $html .= '<li><a class="J_menuItem" href="'.url($valFir['url']).'">'. $valFir['name'] .'</a></li>';
				                foreach($valFir['child'] as $keySec => $valSec){
				                $html .= '<li>';
				                    $html .= '<a href="javascript:void(0)">'. $valSec['name'] .' <span class="fa arrow"></span></a>';
				                    $html .= '<ul class="nav nav-third-level">';
				                        $html .= '<li><a class="J_menuItem" href="'.url($valSec['url']).'">'. $valSec['name'] .'</a></li>';
				                    $html .= '</ul>';
				                $html .= '</li>';
				            	}
				            $html .= '</ul>';
			        	} else {
			        		$html .= '<ul class="nav nav-second-level">';
			        		$html .= '<li><a class="J_menuItem" href="'.url($valFir['url']).'">'. $valFir['name'] .'</a></li>';
			        		$html .= '</ul>';
			        	}
		        	}
	            }
	        $html .= '</li>';
		}
		return $html;

	}



	/**
	 * 获取当前菜单名称 / 当前位置
	 */
	private function getNowMenuName ($type = 'all') {
		$lists = cache('Menu');
		
		$nowUrl = strtolower(Request::module() . '/' . Request::controller() . '/' . Request::action());
		
		if($nowUrl == 'admin/index/index'){
			return 'index';
		}else{
			foreach($lists as $key => $val){
				if($val['url'] == $nowUrl){
					if($type == 'all'){
						return array('topid' => $val['topid'], 'name' => $val['name']);
					}
					if($type == 'topid'){
						return $val['topid'];
					}
					if($type == 'name'){
						return $val['name'];
					}
				}
			}
		}
		return 'unknow';
	}


	/**
	 * 检测站点是否已关闭:在公共控制器初始化方法中调用
	 */
	public function isOpen() {
        // 获取当前站点的状态
        if (Cache::get('isOpenType')) {
        	$isOpen = Cache::get('isOpenType');
        } else {
        	$isOpen = model('setting')->where(['name'=>'OPEN_TYPE'])->value('values');
        	Cache::set('isOpenType', $isOpen);
        }
        
        // 如果站点是关闭状态,那我们只允许关闭前台模块,后台模块必须仍然可以访问
        if ($isOpen==0 && Request::module()=='index') {
            //或者写上:此域名出售
            $info = <<<'INFO'
            <body style="background-color:#333">
            <h1 style="color:#eee;text-align:center;margin:200px">站点维护中...</h1>
            </body>
INFO;
            exit($info);
        }
    }






}