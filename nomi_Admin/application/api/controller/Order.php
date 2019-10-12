<?php
namespace app\api\controller;
use app\common\controller\Apibase;
use think\facade\Request;

class Order extends Apibase
{	

    // 优先加载
    public function  initialize() {
        parent::initialize();
    }


    /**
     * 购物车页面
     */
    public function shopCar() {

    	$car = model('OrderCar')->where('uid=:id', ['id' => $this->uid])->order('add_time desc')->select();

        // 当前用户会员优惠价
        $pick_price = model('user')
            ->alias('u')
            ->field('l.level_id, l.pick_price, l.level_type')
            ->leftJoin('user_level l', ['u.level = l.level_id'])
            ->where('u.user_id=:id', ['id' => $this->uid])
            ->find();

    	$lists = array();
    	
    	if (!empty($car)) {

    		foreach ($car as $k => $v) {

	    		$product[$k] = model('Product')
	    			->alias('p')
	    			->field('p.pro_id, p.name, p.price_after, p.photo, p.intro, p.combo, car.car_id, car.buy_num, car.add_time, wp.current_cnt')
	    			->join('order_car car', 'p.pro_id = car.pro_id')
	    			->leftJoin('warehouse_product wp', 'p.pro_id = wp.proid')
    				->where('p.pro_id=:id', ['id' => $v['pro_id']])
	    			->order('car.add_time desc')
	    			->find();

	    		if (!empty($product)) {
	    			
	    			foreach ($product as $kkk => $vvv) {

                        if ($vvv['combo'] == 0) {
                            $lists[$kkk]['price'] = sprintf("%.2f", ($vvv['price_after'] * $pick_price['pick_price']));
                        } else {
                            $lists[$kkk]['price'] = $vvv['price_after'];
                        }

                        $lists[$kkk]['car_id'] = $vvv['car_id'];
	    				$lists[$kkk]['pro_id'] = $vvv['pro_id'];
	    				$lists[$kkk]['name'] = $vvv['name'];
	    				$lists[$kkk]['photo'] = URL_PATH.$vvv['photo'];
	    				$lists[$kkk]['current_cnt'] = $vvv['current_cnt'];
	    				$lists[$kkk]['intro'] = $vvv['intro'];
	    				$lists[$kkk]['num'] = 1;
	    				$lists[$kkk]['add_time'] = $vvv['add_time'];
	    				$lists[$kkk]['selected'] = true;

	    			}

	    		}

	    	}	

    	}

    	
    	$showDatas = array(
            'cartsdata'      	=> $lists,
        );
        return json($showDatas);

    }



    /**
     * 加入购物车
     */
    public function addCar() {

        // 产品id
		$data['pro_id'] = Request::param('proid');
		// 选择购买数量
		$data['buy_num'] = Request::param('buy_num');

		$isCar = model('OrderCar')
			->where('pro_id=:id', ['id' => $data['pro_id']])
			->where('uid=:uid', ['uid' => $this->uid])
			->value('car_id');
		if (empty($isCar)) {
			
			// 产品价格
			$data['price'] = Request::param('price') * $data['buy_num'];
			$data['uid'] = $this->uid;
			$data['add_time'] = time();

			if (model('OrderCar')->data($data)->save()) {
				return json(['status'=>1, 'msg' => '成功加入购入车']);
			}

		} else {

			$inputs['buy_num'] = $data['buy_num'];
			$inputs['price'] = Request::param('price') * $data['buy_num'];
			$inputs['add_time'] = time();
			if (model('OrderCar')->save($inputs, ['car_id' => $isCar])) {
				return json(['status'=>1, 'msg' => '成功加入购入车']);
			}

		}

		
    }




    /**
     * 购物车删除
     */
    public function delCar() {

        $car_id = Request::post('car_id');
        if (model('OrderCar')->where('car_id=:id', ['id'=>$car_id])->delete()) {
            return json(['status'=>1, 'msg' => '成功删除商品']);
        }
        return json(['status'=>0, 'msg' => '删除失败']);

    }





    /**
     * 物流页面
     */
    public function master() {

    	// 一级分类
            $category = array(
                '0' => '全部',
                '1' => '待付款',
                '2' => '待发货',
                '3' => '待收货',
                '4' => '退款'
            );

        // 当前价钱
		$pick_price = model('user')
			->alias('u')
			->field('l.level_id, l.pick_price')
			->leftJoin('user_level l', ['u.level = l.level_id'])
			->where('u.user_id=:id', ['id' => $this->uid])
			->find();


    	$type = Request::param('type');

    	// 全部
    	if (empty($type) || $type == 0) {
    		
    		$list = model('OrderMaster')
    			->alias('om')
    			->field('p.pro_id, p.name, p.price_after, p.photo, p.intro, p.combo, om.order_id, om.product_cnt, om.order_status')
    			->leftJoin('product p', ['om.proid = p.pro_id'])
    			->where('om.uid=:id', ['id' => $this->uid])
    			->order('om.create_time desc')
    			->select();
    		foreach ($list as $k => $v) {

    			if ($v['order_status'] == 0) {
    				$list[$k]['topName'] = '等待付款';
    			} elseif ($v['order_status'] == 1) {
    				$list[$k]['topName'] = '待发货';
    			} elseif ($v['order_status'] == 2) {
    				$list[$k]['topName'] = '待收货';
    			} elseif ($v['order_status'] == 3) {
    				$list[$k]['topName'] = '退款';
    			}
    			

    			if (substr($v['photo'], 0, 4) !== 'http') $list[$k]['photo'] = URL_PATH . $v['photo'];

    			if ($v['combo'] > 0) {
    				$v['price_after'] = $v['price_after'];
    				$v['count'] = sprintf("%.2f", ($v['product_cnt'] * $v['price_after']));
    			} else {
    				$v['price_after'] = sprintf("%.2f", ($v['price_after'] * $pick_price['pick_price']));
    				$v['count'] = sprintf("%.2f", ($v['product_cnt'] * $v['price_after']));
    			}

    		}

    	// 待付款
    	} elseif ($type == 1) {
    		
    		$list = model('OrderMaster')
    			->alias('om')
    			->field('p.pro_id, p.name, p.price_after, p.photo, p.intro, p.combo, om.order_id, om.product_cnt, om.order_status')
    			->leftJoin('product p', ['om.proid = p.pro_id'])
    			->where('om.uid=:id', ['id' => $this->uid])
    			->where(['om.order_status'=>0])
    			->order('om.pay_time desc')
    			->select();
    		foreach ($list as $k => $v) {

    			$list[$k]['topName'] = '等待付款';

    			if (substr($v['photo'], 0, 4) !== 'http') $list[$k]['photo'] = URL_PATH . $v['photo'];

    			if ($v['combo'] > 0) {
    				$v['price_after'] = $v['price_after'];
    				$v['count'] = sprintf("%.2f", ($v['product_cnt'] * $v['price_after']));
    			} else {
    				$v['price_after'] = sprintf("%.2f", ($v['price_after'] * $pick_price['pick_price']));
    				$v['count'] = sprintf("%.2f", ($v['product_cnt'] * $v['price_after']));
    			}

    		}

    	// 待发货
    	} elseif ($type == 2) {
    		
    		$list = model('OrderMaster')
    			->alias('om')
    			->field('p.pro_id, p.name, p.price_after, p.photo, p.intro, p.combo, om.order_id, om.product_cnt, om.order_status')
    			->leftJoin('product p', ['om.proid = p.pro_id'])
    			->where('om.uid=:id', ['id' => $this->uid])
    			->where(['om.order_status'=>1])
    			->order('om.pay_time desc')
    			->select();
    		foreach ($list as $k => $v) {

    			$list[$k]['topName'] = '等待发货';

    			if (substr($v['photo'], 0, 4) !== 'http') $list[$k]['photo'] = URL_PATH . $v['photo'];

    			if ($v['combo'] > 0) {
    				$v['price_after'] = $v['price_after'];
    				$v['count'] = sprintf("%.2f", ($v['product_cnt'] * $v['price_after']));
    			} else {
    				$v['price_after'] = sprintf("%.2f", ($v['price_after'] * $pick_price['pick_price']));
    				$v['count'] = sprintf("%.2f", ($v['product_cnt'] * $v['price_after']));
    			}

    		}

    	// 待收货
    	} elseif ($type == 3) {

    		$list = model('OrderMaster')
    			->alias('om')
    			->field('p.pro_id, p.name, p.price_after, p.photo, p.intro, p.combo, om.order_id, om.product_cnt, om.order_status')
    			->leftJoin('product p', ['om.proid = p.pro_id'])
    			->where('om.uid=:id', ['id' => $this->uid])
    			->where(['om.order_status'=>2])
    			->order('om.pay_time desc')
    			->select();
    		foreach ($list as $k => $v) {

    			$list[$k]['topName'] = '等待收货';

    			if (substr($v['photo'], 0, 4) !== 'http') $list[$k]['photo'] = URL_PATH . $v['photo'];

    			if ($v['combo'] > 0) {
    				$v['price_after'] = $v['price_after'];
    				$v['count'] = sprintf("%.2f", ($v['product_cnt'] * $v['price_after']));
    			} else {
    				$v['price_after'] = sprintf("%.2f", ($v['price_after'] * $pick_price['pick_price']));
    				$v['count'] = sprintf("%.2f", ($v['product_cnt'] * $v['price_after']));
    			}

    		}

    	// 退款
    	} elseif ($type == 4) {
    		
    		$list = model('OrderMaster')
    			->alias('om')
    			->field('p.pro_id, p.name, p.price_after, p.photo, p.intro, p.combo, om.order_id, om.product_cnt, om.order_status')
    			->leftJoin('product p', ['om.proid = p.pro_id'])
    			->where('om.uid=:id', ['id' => $this->uid])
    			->where(['om.order_status'=>3])
    			->order('om.pay_time desc')
    			->select();
    		foreach ($list as $k => $v) {

    			$list[$k]['topName'] = '退款';

    			if (substr($v['photo'], 0, 4) !== 'http') $list[$k]['photo'] = URL_PATH . $v['photo'];

    			if ($v['combo'] > 0) {
    				$v['price_after'] = $v['price_after'];
    				$v['count'] = sprintf("%.2f", ($v['product_cnt'] * $v['price_after']));
    			} else {
    				$v['price_after'] = sprintf("%.2f", ($v['price_after'] * $pick_price['pick_price']));
    				$v['count'] = sprintf("%.2f", ($v['product_cnt'] * $v['price_after']));
    			}

    		}

    	}


    	$showDatas = array(
            'status'        => 1,
            'category'      => $category,
            'list'         => $list,
            'type'         => $type,

        );
        return json($showDatas);
    }





    /**
     * 取消订单
     */
    public function onCancel($orderId) {

    	$order = model('OrderMaster')->where('order_id=:id', ['id' => $orderId])->value('order_status');
    	if ($order == 0) {
    		
    		$del = model('OrderMaster')->where('order_id=:id', ['id' => $orderId])->delete();
    		if ($del) {
    			return json(['status'=>1, 'msg'=>'删除成功']);
    		}

    	}
    	return json(['status'=>0, 'msg'=>'删除失败']);

    }




    /**
     * 确认收货
     */
    public function onAffirm($orderId) {

        $order = model('OrderMaster')->where('order_id=:id', ['id' => $orderId])->value('order_status');
        if ($order == 2) {
            
            $data['order_status'] = $order+1;
            $save = model('OrderMaster')->save($data, ['order_id'=>$orderId]);
            if ($del) {
                return json(['status'=>1, 'msg'=>'收货成功']);
            }

        }
        return json(['status'=>0, 'msg'=>'收货失败']);

    }

    


    
    
    
}