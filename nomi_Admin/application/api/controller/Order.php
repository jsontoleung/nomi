<?php
namespace app\api\controller;
use app\common\controller\Apibase;
use think\facade\Request;
use think\Db;

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
                ->where('om.type',0)
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
                ->where('om.type',0)
    			->order('om.create_time desc')
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
                ->where('om.type',0)
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
                ->where('om.type',0)
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
                ->where('om.type',0)
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
     * 我的预约
     */
    public function appint() {

        $appint_all = model('OrderMaster')
                ->alias('om')
                ->field('p.pro_id, p.name as pname,p.photo,om.order_sn,om.product_cnt,om.order_id,s.*')
                ->leftJoin('product p', ['om.proid = p.pro_id'])
                ->leftJoin('serve_goods s', ['om.order_id =s.order_id'])
                ->where('om.uid=:id', ['id' => $this->uid])
                ->where(['om.order_status'=>1])
                ->where('om.type',1)
                ->order('s.serve_goods_id desc')
                // ->group('s.order_id')
                ->select();
        $appint_ids=array();     
        $appint=array();
        foreach ($appint_all as $key => $value) {
            if(!in_array($value['order_sn'], $appint_ids)){
                $appint_ids[]=$value['order_sn'];
                $appint[]=$value;
            }
        }

        foreach ($appint as $key => $value) {
            if (substr($value['photo'], 0, 4) !== 'http') $appint[$key]['photo'] = URL_PATH . $value['photo'];

            $appint[$key]['make_time']=date('Y-m-d H:i',$value['make_time']);
            // 已使用
            $make_list=Db::name('ServeGoods')
                    ->where(['order_id'=>$value['order_id'],'is_sign_in'=>1])
                    ->select();
            foreach ($make_list as $k => $v) {
                $make_list[$k]['make_time']=date('Y-m-d H:i',$v['make_time']);
            }
            // 已使用次数
            $make_num=Db::name('ServeGoods')
                    ->where(['order_id'=>$value['order_id'],'is_sign_in'=>1])
                    ->count();
            $appint[$key]['make_num']=$make_num;
            $appint[$key]['name']=$value['pname'];
            // 剩余次数
            $appint[$key]['s_num']=$value['product_cnt']-$make_num;
            $appint[$key]['sub']=$make_list;
        }
        

        $showDatas = array(
            'appint'        => $appint

        );
        return json($showDatas);
    }
    /**
     * 获取二维码
     */
    public function getqrcode() {
        $order_date=$_POST;
        if($order_date){
            $qrcode= URL_PATH . '/uploads/appointment/appointment_'.$order_date['order_sn'].'.jpg';

            $showDatas = array(
                'code'        =>0,
                'qrcode'      =>$qrcode
            );
        }else{
            $showDatas = array(
                'code'        =>1,
            );
        }
        
        return json($showDatas);
    }
    /**
     * 修改预约
     */
    public function updateappint() {

        $order_date=$_POST;

        $appint = model('OrderMaster')
                ->alias('om')
                ->field('p.pro_id, p.name as pname,p.price_after,p.photo,om.order_sn,om.product_cnt,om.order_id,s.*')
                ->leftJoin('product p', ['om.proid = p.pro_id'])
                ->leftJoin('serve_goods s', ['om.order_id =s.order_id'])
                ->where('om.uid=:id', ['id' => $this->uid])
                ->where(['om.order_status'=>1])
                ->where('om.type',1)
                ->where('om.order_sn',$order_date['order_sn'])
                ->group('s.order_id')
                ->order('s.serve_goods_id desc')
                ->find();

        if (substr($appint['photo'], 0, 4) !== 'http') $appint['photo'] = URL_PATH . $appint['photo'];
            
        $appint['make_date']=date('Y-m-d',$appint['make_time']);
        $appint['make_time']=date('H:i', $appint['make_time']);
        // 已使用次数
        $make_num=Db::name('ServeGoods')
                ->where(['order_id'=>$appint['order_id'],'is_sign_in'=>1])
                ->count();
        $appint['make_num']=$make_num;
        // 剩余次数
        $appint['s_num']=$appint['product_cnt']-$make_num;
        
        // 当天时间到下一年时间
        $thisday=date('Y-m-d',time());
        $nextyear=date('Y-m-d',strtotime('+1 year',time()));
        // 服务，选择店铺
        $shop = model('Shop')->select();
        $stroe=array();
        $stroe= model('Shop')
                ->alias('p')
                ->field('p.shop_id, p.shop_name, a.area')
                ->leftJoin('areas a', ['p.area = a.areaid'])
                ->where('p.pro_id' ,'like', "%$appint[pro_id]%")
                ->select();

        $showDatas = array(
            'appint'        => $appint,
            'stroe'         => $stroe,
            'thisday'       => $thisday,
            'nextyear'      => $nextyear,

        );
        return json($showDatas);
    }
    /**
     * 确定修改和确定预约
     */
    public function updateappdate() {

        $order_date=$_POST;

        $order_info = model('OrderMaster')
                ->alias('om')
                ->field('s.*')
                ->leftJoin('serve_goods s', ['om.order_id =s.order_id'])
                ->where('om.uid=:id', ['id' => $this->uid])
                ->where(['om.order_status'=>1])
                ->where('om.type',1)
                ->where('om.order_sn',$order_date['order_sn'])
                ->group('s.order_id')
                ->order('s.serve_goods_id desc')
                ->find();
        if($order_date['type']==1){
            // 确定修改
            $server['shopid'] = $order_date['shopid'];
            $server['name']=$order_date['name'];
            $server['phone']=$order_date['phone'];
            $server['store']=$order_date['stroe'];
            $currentTime = $order_date['dates'] . ' ' . $order_date['apptime'];
            $server['make_time']=strtotime($currentTime);
            // 添加订单主表信息
            $serve_add = Db::name('serve_goods')->where('serve_goods_id',$order_info['serve_goods_id'])->update($server);

        }elseif($order_date['type']==2){
            // 再次预约
            $server['order_id']=$order_info['order_id'];
            $server['shopid'] = $order_date['shopid'];
            $server['proid']=$order_date['proid'];
            $server['name']=$order_date['name'];
            $server['phone']=$order_date['phone'];
            $server['store']=$order_date['stroe'];
            $currentTime = $order_date['dates'] . ' ' . $order_date['apptime'];
            $server['make_time']=strtotime($currentTime);
            // 添加订单主表信息
            $serve_add = Db::name('serve_goods')->insert($server);
        }
        
        if($serve_add){
            $showDatas = array(
                'code'        =>0,
            );
        }else{
            $showDatas = array(
                'code'        =>1,
            );
        }

        
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