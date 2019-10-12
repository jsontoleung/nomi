<?php
namespace app\common\controller;
use think\Controller;
use think\facade\Request;

Class Apibase extends Controller {

	public $uid = 0;
	public $openid = 0;
	public $user = 0;

	/**
	 * 优先加载
	 */
	public function initialize() {

		$this->uid = Request::param('uid');
		$this->openid = Request::param('openid');
		$this->user = session('user');

		if (empty($this->user) || !isset($this->user) || $this->user == false) {
			
			// 未登陆，跳转到登陆页
			session(null);
			return redirect("login/getsessionkey");
			exit();

		}
		

		$showDatas = array(

			'uid' 			=> $this->uid,
			'openid' 		=> $this->openid,

		);

		return $showDatas;

	}









}