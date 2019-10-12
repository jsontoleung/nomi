<?php
namespace app\admin\controller;
use app\common\controller\Adminbase;
use app\common\config\Categorys;
use think\facade\Request;
use think\facade\Cache;
use think\Db;
/*
** 订单管理 控制器
*/

class Order extends Adminbase {
	private static $_order = null; // 数据表对象

	// 优先加载
	public function  initialize() {
		parent::initialize();
		self::$_order = model('Order');
	}


	public function product() {

		$lists = self::$_order->proInfo();

		return view('product', ['lists' => $lists]);

	}




	/**
	 * 确认发货
	 */
	public function addDepart() {

		$orderid = Request::param('id');

		$shipping = Categorys::categoryShipping();

		// 确认收货
		if (Request::isPost()) {
			
			$order = Request::post();

			$order = model('OrderMaster')->field('order_id, order_status, logistic_name,logistic_sn')->where('order_id=:id', ['id' => $order['order_id']])->find();

			if (empty($order)) {
				return json(['status'=>0, 'msg'=>'没有订单信息']);
			} else {

				Db::startTrans();
				try {

					$data['order_status'] = $order['order_status'] + 1;
					$data['logistic_name'] = $order['logistic_name'];
					$data['logistic_sn'] = $order['logistic_sn'];
					$saverMaster = model('OrderMaster')->save($data, ['order_id' => $order['order_id']]);

					// 记录物流第一条数据
			        $datas['orderid'] = $order['order_id'];
			        $datas['type'] = 1;
			        $datas['content'] = '包裹正在等待揽收';
			        $datas['add_time'] = time();
			        $saveLogistic = model('logistic')->data($datas)->save();

			        $requestData= "{'OrderCode':'','ShipperCode':'YTO','LogisticCode':'12345678'}";

			        if ($saverMaster && $saveLogistic) {
			        	Db::commit();
						return json(['status'=>1, 'msg'=>'操作成功']);
					} else {
						return json(['status'=>0, 'msg'=>'操作失败']);
					}
					

				} catch (Exception $e) {
					Db::rollback();
                	throw $e;
				}

			}

		}

		return view('depart', ['orderid' => $orderid, 'shipping' => $shipping]);

	}









	/**
	 * 查看物流
	 */
	public function Logistic($id) {

		$list = model('OrderMaster')->field('order_id, logistic_name, logistic_sn')->where('order_id=:id', ['id' => $id])->find();


		return view('logistic', ['list' => $list]);

	}



	





}