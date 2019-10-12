<?php
namespace app\admin\controller;
use app\common\controller\Adminbase;
use app\common\config\ExportExecl;
use think\facade\Request;
use think\facade\Cache;
/*
** 记录 控制器
*/

class Record extends Adminbase {
	private static $_record = null; // 数据表对象

	// 优先加载
	public function  initialize() {
		parent::initialize();
		// 实例化数据表模型
		self::$_record = model('Record');
	}




	// 会员消费记录
	public function memberinfo () {

		$channel_id = Request::param('channel_id') ? Request::param('channel_id') : '';

		$lists = self::$_record->memberList();

		return view('memberinfo', ['lists' => $lists, 'channel_id' => $channel_id]);
	}




	// 企业体系
	public function entSystem($id) {

		$list = self::$_record->entSystem($id);

		return view('entsystem', ['list' => $list]);

	}




	// 登陆记录
	public function loginInfo() {

		$lists = self::$_record->loginInfo();

		return view('logininfo', ['lists' => $lists]);

	}




	// 商品购买记录
	public function productInfo() {

		$channel_id = Request::param('channel_id') ? Request::param('channel_id') : '';

		$lists = self::$_record->productList();

		return view('productinfo', ['lists' => $lists, 'channel_id' => $channel_id]);
	}



	// 企业体系
	public function proRetail($id) {

		$list = model('OrderRetail')->where(['order_id'=>$id])->find();

		return view('prosystem', ['list' => $list]);

	}



	// 导出商品EXCEL表
	public function excelOut() {

		$channel_id = Request::param('channel_id') ? Request::param('channel_id') : '';

		$result = self::$_record->productList($channel_id);
		
		$xlsCell  = array(
	        array('order_id','序号'),
	        array('order_sn','订单号'),
	        array('nickname','购买者'),
	        array('name','购买产品'),
	        array('product_cnt','购买数量'),
	        array('shipping_user','收货人'),
	        array('shipping_phone','联系电话'),
	        array('province','省'),
	        array('city','市'),
	        array('area','区/县'),
	        array('address','详细地址'),
	        array('order_money','订单金额'),
	        array('payment_money','支付金额'),
	        array('give_one','一级返佣'),
	        array('give_two','二级返佣'),
	        array('pay_time','支付时间'),
	        array('method','支付方式'),
	        array('c_pond','C池'),
	        array('b_pond','B池'),
	        array('a_pond','A池'),
	        array('p_pond','P池'),
	        array('h_pond','H池'),
	        array('t_pond','T池'),
	        array('k_pond','K池'),
	        array('s_pond','S池'),
	        array('z_pond','Z池'),
	        array('g_pond','G池'),
	        array('r_pond','R池'),
	        array('n_pond','N池'),
	    );

	    $xlsName = date('Y-m-d').'-产品订单表';
	    $total = count($result);

	    ExportExecl::exportExcel($xlsName, $xlsCell, $result, $total);

	}




	// 导出会员EXCEL表
	public function memberOut() {

		$channel_id = Request::param('channel_id') ? Request::param('channel_id') : '';

		$result = self::$_record->memberList($channel_id);
		
		$xlsCell  = array(
	        array('order_id','序号'),
	        array('order_sn','订单号'),
	        array('channel_name','所属渠道'),
	        array('nickname','购买用户'),
	        array('level_type','购买等级'),
	        array('order_money','订单金额'),
	        array('payment_money','支付金额'),
	        array('two_nickname','二级分销用户'),
	        array('second_level_money','二级返佣金额'),
	        array('second_level_integral','二级返佣积分'),
	        array('one_nickname','一级分销用户'),
	        array('one_level_money','一级返佣金额'),
	        array('one_level_integral','一级返佣积分'),
	        array('pay_time','支付时间'),
	        array('c_pond','C池'),
	        array('b_pond','B池'),
	        array('a_pond','A池'),
	        array('p_pond','P池'),
	        array('h_pond','H池'),
	        array('t_pond','T池'),
	        array('k_pond','K池'),
	        array('s_pond','S池'),
	        array('z_pond','Z池'),
	        array('g_pond','G池'),
	        array('r_pond','R池'),
	        array('n_pond','N池'),
	    );

	    $xlsName = date('Y-m-d').'-会员订单表';
	    $total = count($result);

	    ExportExecl::exportExcel($xlsName, $xlsCell, $result, $total);

	}





}