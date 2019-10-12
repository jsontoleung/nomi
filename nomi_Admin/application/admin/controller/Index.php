<?php
namespace app\admin\controller;
use app\common\controller\Adminbase;
use think\Db;

/*
** 后台首页
*/

class Index extends Adminbase {
	
	// 优先加载
	public function  initialize() {
		parent::initialize();
		
	}
	
	public function index() {

		$uid = session('uid');
		$level = model('admin')->where('id=:id', ['id'=>$uid])->value('level');

		if ($uid == 1) {
			
			// 产品订单数量
			$pro = model('OrderMaster')->where(['order_status'=>1])->count();

		} elseif ($level == 1) {
			
			// 产品订单数量
			$pro = model('OrderMaster')->where(['order_status'=>1])->count();

		} else {
			$pro = 0;
		}

		if ($pro > 0) {
			$getCheck['check'][0]['name'] = '产品订单审核';
			$getCheck['check'][0]['count'] = $pro . '条未读消息';
			$getCheck['check'][0]['url'] = 'order/product';
			$getCheck['count'] = $pro;
		} else {
			$getCheck = array();
		}

		// if (!empty($getCheck) && isset($getCheck)) {
			
		// 	$checkInfo = '';

		// 	$checkInfo .= '<li class="dropdown">';
  //               $checkInfo .= '<a class="dropdown-toggle count-info" data-toggle="dropdown" href="javascript:void(0)">';
  //                   $checkInfo .= '<i class="fa fa-bell"></i> <span class="label label-primary">' .$getCheck['count']. '</span>';
  //               $checkInfo .= '</a>';
  //               $checkInfo .= '<ul class="dropdown-menu dropdown-alerts">';
  //                   foreach ($getCheck['check'] as $k => $v) {
                    	
	 //                    $checkInfo .= '<li>';
	 //                        $checkInfo .= '<a href="' .$v['url']. '">';
	 //                            $checkInfo .= '<div>';
	 //                                $checkInfo .= '<i class="fa fa-envelope fa-fw"></i>' . $v['name'];
	 //                                $checkInfo .= '<span class="pull-right text-muted small">'. $v['count'] .'</span>';
	 //                            $checkInfo .= '</div>';
	 //                        $checkInfo .= '</a>';
	 //                    $checkInfo .= '</li>';
	 //                    $checkInfo .= '<li class="divider"></li>';

  //                   }
  //               $checkInfo .= '</ul>';
  //           $checkInfo .= '</li>';

		// } else {
		// 	$checkInfo = '';
		// }
		
		return view('index', [
			'getCheck' => $getCheck,
			// 'checkInfo' => $checkInfo,
		]);
	}

	public function index_v1() {

		return view('index_v1', [
			'sysinfo' => $this->sysinfo()
		]);
	}

	public function index_v2() {
		
		return view('index_v2');
	}

	public function index_v3() {
		
		return view('index_v3');
	}

	public function index_v4() {
		
		return view('index_v4');
	}

	public function index_v5() {
		
		return view('index_v5');
	}
	
	// 系统信息
	private function sysinfo() {
		
		$mysql = empty($mysql) ? '未知' : $mysql;
		$version = Db::query('SELECT VERSION() AS ver');
		return array(
			'访问者浏览器'		=> browse_info(),
			'运行环境' 			=> $_SERVER["SERVER_SOFTWARE"],
			'网站域名'			=> $_SERVER['HTTP_HOST'],
            '网站目录'  			=> $_SERVER['DOCUMENT_ROOT'],
            '服务器操作系统'      => PHP_OS,
            '服务器端口'    	 	=> $_SERVER['SERVER_PORT'],
            '服务器IP'       	=> $_SERVER['SERVER_ADDR'],
            'WEB运行环境'     	=> $_SERVER['SERVER_SOFTWARE'],
            'MySQL数据库版本'    => PHP_VERSION,
            '运行PHP版本'   		=> $version[0]['ver'],
			'PHP 运行方式' 		=> php_sapi_name(),
			'PHP 版本' 			=> PHP_VERSION,
			'上传附件限制' 		=> ini_get('upload_max_filesize'),
			'执行时间限制' 		=> ini_get('max_execution_time') . 's',
			'剩余空间' 			=> round((@disk_free_space('.') / (1024 * 1024)), 2) . 'M',
		);
	}
	
}
