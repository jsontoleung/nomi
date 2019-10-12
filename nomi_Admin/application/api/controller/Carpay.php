<?php
namespace app\api\controller;
use think\exception\HttpResponseException;
use think\Response;
use lib\WeixinPay;
use think\facade\Request;
use think\Db;
use Log;

// 购物车支付订单
class Carpay	 {
	
	/**
	 * @author 会员支付订单
	 * @param $list 	=> 所有商品 array
	 * @param $openid 	=> openid
	 * @param $total 	=> 订单总金额
	 */
	public function pay () {

		if (Request::isPost()) {
			
			$list = json_decode(Request::post('list'), true);
			$openid = Request::param('openid');
			$total = Request::param('total');

	    	// 购买用户
	    	$user = model('User')->field('user_id, channel_id')->where('openid=:id', ['id'=>$openid])->find();
	    	if (empty($user)) {
	    		return json(['status'=>0, 'msg' => '没有该用户数据']);
	    	}
	    	// 收货地址
	    	$region = model('UserAddr')
				->field('name, phone, province, city, area, address')
				->where('uid=:id', ['id' => $user['user_id']])
				->order(['is_detault' => 1])
				->find();
			if (empty($region)) {
	    		return json(['status'=>0, 'msg' => '请选择收货地址']);
			}

			$appid = APPID;							// 小程序appid
			$openid = $openid;						// 用户openid
			$mch_id = MCHID;						// 商户号
			$key = KEY;								// 商户密钥
			$out_trade_no = $mch_id.time();			// 订单号
			$total_fee = $total;					// 订单金额
			$notify_url = 'https://www.nomiyy.com/index.php/api/pay/notify';
			if ($total_fee) {
				$body = "糯米芽支付";
	            $total_fee = floatval($total_fee * 100);
			}

			$weixinpay = new WeixinPay($appid,$openid,$mch_id,$key,$out_trade_no,$body,$total_fee,$notify_url);
	        $return = $weixinpay->pay();
			
			if ($return) {
				
				foreach ($list as $k => $v) {
					
					if ($v['selected'] == true) {
						
						// 库存表
						$ware_pro = model('WarehouseProduct')->where('proid=:id', ['id' => $v['pro_id']])->select();
						
						foreach ($ware_pro as $kk => $vv) {
							
							if ($v['num'] > $vv['current_cnt']) {
								return json(['status'=>0, 'msg' => '库存不足']);
							}

						}

						$inputs['order_sn'] = $out_trade_no;
						$inputs['channel_id'] = $user['channel_id'];
						$inputs['uid'] = $user['user_id'];
						$inputs['proid'] = $v['pro_id'];
						$inputs['w_id'] = $vv['wp_id'];
						$inputs['product_cnt'] = $v['num'];
						$inputs['shipping_user'] = $region['name'];
						$inputs['shipping_phone'] = $region['phone'];
						$inputs['province'] = $region['province'];
						$inputs['city'] = $region['city'];
						$inputs['area'] = $region['area'];
						$inputs['address'] = $region['address'];
						$inputs['payment_method'] = 5;
						$inputs['order_money'] = $v['num'] * $v['price'];
						$inputs['order_point'] = round($v['num'] * $v['price'], 0);
						$inputs['create_time'] = time();
						
						// 添加订单主表信息
						$add_master[$k] = model('orderMaster')->insert($inputs);

					}

				}
				if ($add_master) {
					return json(['status'=>1, 'data' => $return, 'order_sn' => $out_trade_no]);
				}

			}

		}// isPost

		

	}





	/**
	 * 支付回调
	 */
	public function notify () {

		error_reporting(E_ALL);
		$postXml = file_get_contents("php://input");
		if (empty($postXml)) {
            return json(['status'=>0, 'msg' => '没有任何参数']);
        }

        //将xml格式转换成数组
        function xmlToArray($xml) {
            //禁止引用外部xml实体
            libxml_disable_entity_loader(true);
            $xmlstring = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
            $val = json_decode(json_encode($xmlstring), true);
            return $val;
        }
        $attr = xmlToArray($postXml);
        $total_fee = $attr['total_fee'];
        $open_id = $attr['openid'];
        $out_trade_no = $attr['out_trade_no'];
        $time = $attr['time_end'];
        Log::write($attr);

        // 订单信息
        $orderMaster = model('orderMaster')
        	->alias('om')
            ->field('om.order_id, om.order_sn, om.channel_id, om.uid, om.proid, cr.*')
        	->Join('menber_retail cr', ['om.channel_id = cr.channel_id'])
        	->where('order_sn=:sn', ['sn' => $out_trade_no])
        	->select();
        if (empty($userLevel)) {
			Log::write('获取订单信息--失败');
		}

		// 查找当前用户
    	$user = model('user')
    		->field('user_id, pid, integral, return_money')
    		->where('user_id=:id', ['id' => $v['uid']])
    		->find();
    	if (empty($userLevel)) {
			Log::write('获取当前用户--失败');
		}


    	// 查找上级
    	$topUser = model('user')
    		->field('user_id, pid, integral, team_integral, return_money')
    		->where('user_id=:id', ['id' => $user['pid']])
    		->find();

    	// 查找上上级
		$parentUser = model('user')
    		->field('user_id, integral, team_integral, return_money')
    		->where('user_id=:id', ['id' => $topUser['pid']])
    		->find();


        // 商品池分配按---->会员分配
		$userLevel = model('Userlevel')->where('level_id=:id', ['id' => 3])->find();
		if (empty($userLevel)) {
			Log::write('获取池分配信息--失败');
		}

        foreach ($orderMaster as $k => $v) {

        	// 仓库信息
        	$warePro = model('WarehouseProduct')->where('proid=:id', ['id' => $v['proid']])->select();
        	if (!empty($warePro)) {
        		
        		foreach ($warePro as $kk => $vv) {
        			$inputs['current_cnt'] = $vv['current_cnt'] - $v['product_cnt'];
        			$wareSave[$kk] = model('WarehouseProduct')->where(['wp_id' => $vv['wp_id']])->update($inputs);
        			if (!$wareSave) {
        				Log::write('更新仓库数据失败');
        			}
        		}

        	}
        	
        	// 商品信息
        	$proInfo = model('product')->where('pro_id=:id', ['id' => $v['proid']])->select();
        	foreach ($proInfo as $kkk => $vvv) {

        		if (empty($topUser)) {// 顶级

        			// 订单回调更改
		        	$data['payment_money'] = floatval($v['order_money'] / 100);
			        $data['pay_time'] = time();
			        $data['update_time'] = time();
			        $data['pay_status'] = 1;
			        $data['order_status'] = 1;
			        $save = model('orderMaster')->save($data, ['order_sn' => $v['order_sn']]);
			        if (!$save) {
		    			Log::write('顶级：订单回调---失败');
		    		}

        			// 购买该商品直接升级当前会员
			        if ( ($vvv['is_member'] == 1) && ($vvv['level'] > $user['level']) ) {

			        	// 购买用户积分返佣
			    		$res['integral'] = $user['integral'] + round($total_fee, 0);
			    		$res['level'] = $vvv['level'];
			    		$returnInt = model('user')->save($res, ['user_id' => $v['uid']]);
			    		if (!$returnInt) {
			    			Log::write('顶级：用户积分返佣或直接升级会员---失败');
			    		}

			        } else {

			        	// 购买用户积分返佣
			    		$res['integral'] = $user['integral'] + round($total_fee, 0);
			    		$returnInt = model('user')->save($res, ['user_id' => $v['uid']]);
			    		if (!$returnInt) {
			    			Log::write('顶级：用户积分返佣或直接升级会员---失败');
			    		}

			        }


			        // 企业体系返佣记录信息
				    $dataRetail['order_id'] = $v['order_id'];
				    $dataRetail['retail_id'] = $v['retail_id'];
				    $dataRetail['c_pond'] = $userLevel['c_pond'];
				    $dataRetail['b_pond'] = $userLevel['b_pond'];
				    $dataRetail['a_pond'] = $userLevel['a_pond'];
				    $dataRetail['r_pond'] = $userLevel['r_pond'];
				    $dataRetail['add_time'] = time();

		        	// 商品池返佣全有的情况(H、K、S)T池按----P池5%、H池10%、K池20%、S池10%、Z池20%、G池20%、N池15%，进行分配
				    if (!empty($v['h_pond']) && !empty($v['k_pond']) && !empty($v['s_pond'])) {

				        // T池总数 没有上级，加起来
				        $tCount = $userLevel['t_pond'] + $vvv['give_two'];

				        // 企业体系返佣记录信息
				        $dataRetail['h_pond'] = $userLevel['h_pond'] + round($tCount * 0.1, 2);
				        $dataRetail['s_pond'] = $userLevel['s_pond'] + round($tCount * 0.1, 2);
				        $dataRetail['z_pond'] = $userLevel['z_pond'] + round($tCount * 0.2, 2);
				        $dataRetail['g_pond'] = $userLevel['g_pond'] + round($tCount * 0.2, 2);
				        $dataRetail['n_pond'] = $userLevel['n_pond'] + $vvv['give_one'] + round($tCount * 0.15, 2);
				        $dataRetail['p_pond'] = $userLevel['p_pond'] + round($tCount * 0.05, 2);
				        $dataRetail['k_pond'] = $userLevel['k_pond'] + round($tCount * 0.2, 2);
				        // 插入数据
				        $saveRetail = model('OrderRetail')->insert($dataRetail);
				        if (!$saveRetail) {
				        	Log::write('企业体系返佣记录信息--失败');
				        }

				    // 商品池返佣(H 无的情况)T池按 ----P池5%、H池10%、K池20%、S池10%、Z池20%、G池20%、N池15%，进行分配
				    // 计算 H池的10% / 其他池的加起来的总数，分配其他池
				    } elseif (empty($v['h_pond']) && !empty($v['k_pond']) && !empty($v['s_pond'])) {

				        // T池总数 没有H池，没有K池，加起来
				        $tCount = $userLevel['t_pond'] + $userLevel['h_pond'] + $vvv['give_two'];

				        // H池比例
			            $hScale = round(0.1 / 6, 2);

			            $dataRetail['p_pond'] = $userLevel['p_pond'] + round($tCount * (0.05+$hScale), 2);
			            $dataRetail['k_pond'] = $userLevel['k_pond'] + round($tCount * (0.2+$hScale), 2);
			            $dataRetail['s_pond'] = $userLevel['s_pond'] + round($tCount * (0.1+$hScale), 2);
			            $dataRetail['z_pond'] = $userLevel['z_pond'] + round($tCount * (0.2+$hScale), 2);
			            $dataRetail['g_pond'] = $userLevel['g_pond'] + round($tCount * (0.2+$hScale), 2);
			            $dataRetail['n_pond'] = $userLevel['n_pond'] + $vvv['give_one'] + round($tCount * (0.15+$hScale), 2);
				        // 插入数据
				        $saveRetail = model('OrderRetail')->insert($dataRetail);
				        if (!$saveRetail) {
				        	Log::write('企业体系返佣记录信息--失败');
				        }
				        
				    
				    // 商品池返佣(H和K 无的情况)T池按 ----P池5%、H池10%、K池20%、S池10%、Z池20%、G池20%、N池15%，进行分配
				    // 计算 H池的10% + K池的20% / 其他池的加起来的总数，分配其他池
				    } elseif (empty($v['h_pond']) && empty($v['k_pond']) && !empty($v['s_pond'])) {

				        // T池总数 没有上级，没有H池，没有K池，加起来
				        $tCount = $userLevel['t_pond'] + $userLevel['h_pond'] + $userLevel['k_pond'] + $vvv['give_two'];

				        // H和K池比例
			            $hScale = round(0.1 / 5, 2);

			            $dataRetail['p_pond'] = $userLevel['p_pond'] + round($tCount * (0.05+$hScale), 2);
			            $dataRetail['s_pond'] = $userLevel['s_pond'] + round($tCount * (0.1+$hScale), 2);
			            $dataRetail['z_pond'] = $userLevel['z_pond'] + round($tCount * (0.2+$hScale), 2);
			            $dataRetail['g_pond'] = $userLevel['g_pond'] + round($tCount * (0.2+$hScale), 2);
			            $dataRetail['n_pond'] = $userLevel['n_pond'] + $vvv['give_one'] + round($tCount * (0.15+$hScale), 2);
				        // 插入数据
				        $saveRetail = model('OrderRetail')->insert($dataRetail);
				        if (!$saveRetail) {
				        	Log::write('企业体系返佣记录信息--失败');
				        }


				    // 商品池返佣(全无的情况)T池按 ----P池5%、H池10%、K池20%、S池10%、Z池20%、G池20%、N池15%，进行分配
				    // 计算 H池的10% + K池的20% + S池的10% / 其他池的加起来的总数，分配其他池
				    } elseif (empty($v['h_pond']) && empty($v['k_pond']) && empty($v['s_pond'])) {

				        // T池总数 没有上级，没有H池，没有K池，没有S池，加起来
	            		$tCount = $userLevel['t_pond'] + $userLevel['h_pond'] + $userLevel['k_pond'] + $userLevel['s_pond'] + $vvv['give_two'];
				        // H和K池比例
			            $hScale = round(0.1 / 4, 2);

			            $dataRetail['p_pond'] = $userLevel['p_pond'] + round($tCount * (0.05+$hScale), 2);
			            $dataRetail['z_pond'] = $userLevel['z_pond'] + round($tCount * (0.2+$hScale), 2);
			            $dataRetail['g_pond'] = $userLevel['g_pond'] + round($tCount * (0.2+$hScale), 2);
			            $dataRetail['n_pond'] = $userLevel['n_pond'] + $vvv['give_one'] + round($tCount * (0.15+$hScale), 2);
				        // 插入数据
				        $saveRetail = model('OrderRetail')->insert($dataRetail);
				        if (!$saveRetail) {
				        	Log::write('企业体系返佣记录信息--失败');
				        }

				    } else {
				    	Log::write('商品池返佣--失败');
				    }




        		} elseif (!empty($topUser) && empty($parentUser)) {	// 有上级，没有上上级

    				// 订单回调更改
		        	$data['payment_money'] = floatval($v['order_money'] / 100);
			        $data['pay_time'] = time();
			        $data['update_time'] = time();
			        $data['pay_status'] = 1;
			        $data['order_status'] = 1;
		        	$data['give_two'] = $vvv['give_two'];
			        $save = model('orderMaster')->save($data, ['order_sn' => $v['order_sn']]);
			        if (!$save) {
		    			Log::write('顶级：订单回调---失败');
		    		}

        			// 购买该商品直接升级当前会员
			        if ( ($vvv['is_member'] == 1) && ($vvv['level'] > $user['level']) ) {

			        	// 购买用户积分返佣
			    		$res['integral'] = $user['integral'] + round($total_fee, 0);
			    		$res['level'] = $vvv['level'];
			    		$returnInt = model('user')->save($res, ['user_id' => $v['uid']]);
			    		if (!$returnInt) {
			    			Log::write('顶级：用户积分返佣或直接升级会员---失败');
			    		}

			        } else {

			        	// 购买用户积分返佣
			    		$res['integral'] = $user['integral'] + round($total_fee, 0);
			    		$returnInt = model('user')->save($res, ['user_id' => $v['uid']]);
			    		if (!$returnInt) {
			    			Log::write('顶级：用户积分返佣或直接升级会员---失败');
			    		}

			        }
	        		
	        		// 上级返佣
        			$top['return_money'] = $topUser['return_money'] + $vvv['give_two'];// 现金返现
        			$top['team_integral'] = $topUser['team_integral'] + round($v['order_money'], 0);// 积分返佣
        			$topSave = model('user')->save($top, ['user_id' => $topUser['user_id']]);
        			if (!$topSave) {
		        		Log::write('有上级，没有上上级：上级返佣---失败');	
        			}


        			// 企业体系返佣记录信息
				    $dataRetail['order_id'] = $v['order_id'];
				    $dataRetail['retail_id'] = $v['retail_id'];
				    $dataRetail['c_pond'] = $userLevel['c_pond'];
				    $dataRetail['b_pond'] = $userLevel['b_pond'];
				    $dataRetail['a_pond'] = $userLevel['a_pond'];
				    $dataRetail['r_pond'] = $userLevel['r_pond'];
				    $dataRetail['add_time'] = time();

		        	// 商品池返佣全有的情况(H、K、S)T池按----P池5%、H池10%、K池20%、S池10%、Z池20%、G池20%、N池15%，进行分配
				    if (!empty($v['h_pond']) && !empty($v['k_pond']) && !empty($v['s_pond'])) {

				        // T池总数
	            		$tCount = $userLevel['t_pond'];

				        // 企业体系返佣记录信息
				        $dataRetail['h_pond'] = $userLevel['h_pond'] + round($tCount * 0.1, 2);
				        $dataRetail['s_pond'] = $userLevel['s_pond'] + round($tCount * 0.1, 2);
				        $dataRetail['z_pond'] = $userLevel['z_pond'] + round($tCount * 0.2, 2);
				        $dataRetail['g_pond'] = $userLevel['g_pond'] + round($tCount * 0.2, 2);
				        $dataRetail['n_pond'] = $userLevel['n_pond'] + $proInfo['give_one'] + round($tCount * 0.15, 2);
				        $dataRetail['p_pond'] = $userLevel['p_pond'] + round($tCount * 0.05, 2);
				        $dataRetail['k_pond'] = $userLevel['k_pond'] + round($tCount * 0.2, 2);
				        // 插入数据
				        $saveRetail = model('OrderRetail')->insert($dataRetail);
				        if (!$saveRetail) {
				        	Log::write('企业体系返佣记录信息--失败');
				        }

				    // 商品池返佣(H 无的情况)T池按 ----P池5%、H池10%、K池20%、S池10%、Z池20%、G池20%、N池15%，进行分配
				    // 计算 H池的10% / 其他池的加起来的总数，分配其他池
				    } elseif (empty($v['h_pond']) && !empty($v['k_pond']) && !empty($v['s_pond'])) {

				        // T池总数 没有H池，加起来
	            		$tCount = $userLevel['t_pond'] + $userLevel['h_pond'];

				        // H池比例
			            $hScale = round(0.1 / 6, 2);

			            $dataRetail['p_pond'] = $userLevel['p_pond'] + round($tCount * (0.05+$hScale), 2);
			            $dataRetail['k_pond'] = $userLevel['k_pond'] + round($tCount * (0.2+$hScale), 2);
			            $dataRetail['s_pond'] = $userLevel['s_pond'] + round($tCount * (0.1+$hScale), 2);
			            $dataRetail['z_pond'] = $userLevel['z_pond'] + round($tCount * (0.2+$hScale), 2);
			            $dataRetail['g_pond'] = $userLevel['g_pond'] + round($tCount * (0.2+$hScale), 2);
			            $dataRetail['n_pond'] = $userLevel['n_pond'] + $proInfo['give_one'] + round($tCount * (0.15+$hScale), 2);
				        // 插入数据
				        $saveRetail = model('OrderRetail')->insert($dataRetail);
				        if (!$saveRetail) {
				        	Log::write('企业体系返佣记录信息--失败');
				        }
				        
				    
				    // 商品池返佣(H和K 无的情况)T池按 ----P池5%、H池10%、K池20%、S池10%、Z池20%、G池20%、N池15%，进行分配
				    // 计算 H池的10% + K池的20% / 其他池的加起来的总数，分配其他池
				    } elseif (empty($v['h_pond']) && empty($v['k_pond']) && !empty($v['s_pond'])) {

				        // T池总数 没有H池，没有K池，加起来
	            		$tCount = $userLevel['t_pond'] + $userLevel['h_pond'] + $userLevel['k_pond'];

				        // H和K池比例
			            $hScale = round(0.1 / 5, 2);

			            $dataRetail['p_pond'] = $userLevel['p_pond'] + round($tCount * (0.05+$hScale), 2);
			            $dataRetail['s_pond'] = $userLevel['s_pond'] + round($tCount * (0.1+$hScale), 2);
			            $dataRetail['z_pond'] = $userLevel['z_pond'] + round($tCount * (0.2+$hScale), 2);
			            $dataRetail['g_pond'] = $userLevel['g_pond'] + round($tCount * (0.2+$hScale), 2);
			            $dataRetail['n_pond'] = $userLevel['n_pond'] + $proInfo['give_one'] + round($tCount * (0.15+$hScale), 2);
				        // 插入数据
				        $saveRetail = model('OrderRetail')->insert($dataRetail);
				        if (!$saveRetail) {
				        	Log::write('企业体系返佣记录信息--失败');
				        }


				    // 商品池返佣(全无的情况)T池按 ----P池5%、H池10%、K池20%、S池10%、Z池20%、G池20%、N池15%，进行分配
				    // 计算 H池的10% + K池的20% + S池的10% / 其他池的加起来的总数，分配其他池
				    } elseif (empty($v['h_pond']) && empty($v['k_pond']) && empty($v['s_pond'])) {

				        // T池总数 没有上级，没有H池，没有K池，没有S池，加起来
	            		$tCount = $userLevel['t_pond'] + $userLevel['h_pond'] + $userLevel['k_pond'] + $userLevel['s_pond'];
				        // H和K池比例
			            $hScale = round(0.1 / 4, 2);

			            $dataRetail['p_pond'] = $userLevel['p_pond'] + round($tCount * (0.05+$hScale), 2);
			            $dataRetail['z_pond'] = $userLevel['z_pond'] + round($tCount * (0.2+$hScale), 2);
			            $dataRetail['g_pond'] = $userLevel['g_pond'] + round($tCount * (0.2+$hScale), 2);
			            $dataRetail['n_pond'] = $userLevel['n_pond'] + round($tCount * (0.15+$hScale), 2);
				        // 插入数据
				        $saveRetail = model('OrderRetail')->insert($dataRetail);
				        if (!$saveRetail) {
				        	Log::write('企业体系返佣记录信息--失败');
				        }

				    } else {
				    	Log::write('商品池返佣--失败');
				    }
        			




	        	} else {	// 有上级和上上级

	        		// 订单回调更改
		        	$data['payment_money'] = floatval($v['order_money'] / 100);
			        $data['pay_time'] = time();
			        $data['update_time'] = time();
			        $data['pay_status'] = 1;
			        $data['order_status'] = 1;
		        	$data['give_two'] = $vvv['give_two'];
	        		$data['give_one'] = $vvv['give_one'];
			        $save = model('orderMaster')->save($data, ['order_sn' => $v['order_sn']]);
			        if (!$save) {
		    			Log::write('顶级：订单回调---失败');
		    		}

        			// 购买该商品直接升级当前会员
			        if ( ($vvv['is_member'] == 1) && ($vvv['level'] > $user['level']) ) {

			        	// 购买用户积分返佣
			    		$res['integral'] = $user['integral'] + round($total_fee, 0);
			    		$res['level'] = $vvv['level'];
			    		$returnInt = model('user')->save($res, ['user_id' => $v['uid']]);
			    		if (!$returnInt) {
			    			Log::write('顶级：用户积分返佣或直接升级会员---失败');
			    		}

			        } else {

			        	// 购买用户积分返佣
			    		$res['integral'] = $user['integral'] + round($total_fee, 0);
			    		$returnInt = model('user')->save($res, ['user_id' => $v['uid']]);
			    		if (!$returnInt) {
			    			Log::write('顶级：用户积分返佣或直接升级会员---失败');
			    		}

			        }

	        		// 上级返佣
        			$top['return_money'] = $topUser['return_money'] + $vvv['give_two'];// 现金返现
        			$top['team_integral'] = $topUser['team_integral'] + round($v['order_money'], 0);// 积分返佣
        			$topSave = model('user')->save($top, ['user_id' => $topUser['user_id']]);
        			if (!$topSave) {
        				Log::write('有上级和上上级：上级返佣---失败');
        			}

        			// 上上级返佣
        			$parent['return_money'] = $parentUser['return_money'] + $vvv['give_one'];// 现金返现
        			$parent['team_integral'] = $parentUser['team_integral'] + round($v['order_money'], 0);// 积分返佣
        			$parentSave = model('user')->save($parent, ['user_id' => $parentUser['user_id']]);
        			if (!$parentSave) {
        				Log::write('有上级和上上级：上上级返佣---失败');	
        			}


        			// 企业体系返佣记录信息
				    $dataRetail['order_id'] = $v['order_id'];
				    $dataRetail['retail_id'] = $v['retail_id'];
				    $dataRetail['c_pond'] = $userLevel['c_pond'];
				    $dataRetail['b_pond'] = $userLevel['b_pond'];
				    $dataRetail['a_pond'] = $userLevel['a_pond'];
				    $dataRetail['r_pond'] = $userLevel['r_pond'];
				    $dataRetail['add_time'] = time();

		        	// 商品池返佣全有的情况(H、K、S)T池按----P池5%、H池10%、K池20%、S池10%、Z池20%、G池20%、N池15%，进行分配
				    if (!empty($v['h_pond']) && !empty($v['k_pond']) && !empty($v['s_pond'])) {

				        // T池总数 加起来
	            		$tCount = $userLevel['t_pond'];

				        // 企业体系返佣记录信息
				        $dataRetail['h_pond'] = $userLevel['h_pond'] + round($tCount * 0.1, 2);
				        $dataRetail['s_pond'] = $userLevel['s_pond'] + round($tCount * 0.1, 2);
				        $dataRetail['z_pond'] = $userLevel['z_pond'] + round($tCount * 0.2, 2);
				        $dataRetail['g_pond'] = $userLevel['g_pond'] + round($tCount * 0.2, 2);
				        $dataRetail['n_pond'] = $userLevel['n_pond'] + round($tCount * 0.15, 2);
				        $dataRetail['p_pond'] = $userLevel['p_pond'] + round($tCount * 0.05, 2);
				        $dataRetail['k_pond'] = $userLevel['k_pond'] + round($tCount * 0.2, 2);
				        // 插入数据
				        $saveRetail = model('OrderRetail')->insert($dataRetail);
				        if (!$saveRetail) {
				        	Log::write('企业体系返佣记录信息--失败');
				        }

				    // 商品池返佣(H 无的情况)T池按 ----P池5%、H池10%、K池20%、S池10%、Z池20%、G池20%、N池15%，进行分配
				    // 计算 H池的10% / 其他池的加起来的总数，分配其他池
				    } elseif (empty($v['h_pond']) && !empty($v['k_pond']) && !empty($v['s_pond'])) {

				        // T池总数 没有H池，加起来
	            		$tCount = $userLevel['t_pond'] + $userLevel['h_pond'];

				        // H池比例
			            $hScale = round(0.1 / 6, 2);

			            $dataRetail['p_pond'] = $userLevel['p_pond'] + round($tCount * (0.05+$hScale), 2);
			            $dataRetail['k_pond'] = $userLevel['k_pond'] + round($tCount * (0.2+$hScale), 2);
			            $dataRetail['s_pond'] = $userLevel['s_pond'] + round($tCount * (0.1+$hScale), 2);
			            $dataRetail['z_pond'] = $userLevel['z_pond'] + round($tCount * (0.2+$hScale), 2);
			            $dataRetail['g_pond'] = $userLevel['g_pond'] + round($tCount * (0.2+$hScale), 2);
			            $dataRetail['n_pond'] = $userLevel['n_pond'] + round($tCount * (0.15+$hScale), 2);
				        // 插入数据
				        $saveRetail = model('OrderRetail')->insert($dataRetail);
				        if (!$saveRetail) {
				        	Log::write('企业体系返佣记录信息--失败');
				        }
				        
				    
				    // 商品池返佣(H和K 无的情况)T池按 ----P池5%、H池10%、K池20%、S池10%、Z池20%、G池20%、N池15%，进行分配
				    // 计算 H池的10% + K池的20% / 其他池的加起来的总数，分配其他池
				    } elseif (empty($v['h_pond']) && empty($v['k_pond']) && !empty($v['s_pond'])) {

				        // T池总数 没有上级，没有H池，没有K池，加起来
	           			$tCount = $userLevel['t_pond'] + $userLevel['h_pond'] + $userLevel['k_pond'];

				        // H和K池比例
			            $hScale = round(0.1 / 5, 2);

			            $dataRetail['p_pond'] = $userLevel['p_pond'] + round($tCount * (0.05+$hScale), 2);
			            $dataRetail['s_pond'] = $userLevel['s_pond'] + round($tCount * (0.1+$hScale), 2);
			            $dataRetail['z_pond'] = $userLevel['z_pond'] + round($tCount * (0.2+$hScale), 2);
			            $dataRetail['g_pond'] = $userLevel['g_pond'] + round($tCount * (0.2+$hScale), 2);
			            $dataRetail['n_pond'] = $userLevel['n_pond'] + round($tCount * (0.15+$hScale), 2);
				        // 插入数据
				        $saveRetail = model('OrderRetail')->insert($dataRetail);
				        if (!$saveRetail) {
				        	Log::write('企业体系返佣记录信息--失败');
				        }


				    // 商品池返佣(全无的情况)T池按 ----P池5%、H池10%、K池20%、S池10%、Z池20%、G池20%、N池15%，进行分配
				    // 计算 H池的10% + K池的20% + S池的10% / 其他池的加起来的总数，分配其他池
				    } elseif (empty($v['h_pond']) && empty($v['k_pond']) && empty($v['s_pond'])) {

				        // T池总数 没有上级，没有H池，没有K池，没有S池，加起来
	            		$tCount = $userLevel['t_pond'] + $userLevel['h_pond'] + $userLevel['k_pond'] + $userLevel['s_pond'];
				        // H和K池比例
			            $hScale = round(0.1 / 4, 2);

			            $dataRetail['p_pond'] = $userLevel['p_pond'] + round($tCount * (0.05+$hScale), 2);
			            $dataRetail['z_pond'] = $userLevel['z_pond'] + round($tCount * (0.2+$hScale), 2);
			            $dataRetail['g_pond'] = $userLevel['g_pond'] + round($tCount * (0.2+$hScale), 2);
			            $dataRetail['n_pond'] = $userLevel['n_pond'] + round($tCount * (0.15+$hScale), 2);
				        // 插入数据
				        $saveRetail = model('OrderRetail')->insert($dataRetail);
				        if (!$saveRetail) {
				        	Log::write('企业体系返佣记录信息--失败');
				        }

				    } else {
				    	Log::write('商品池返佣--失败');
				    }




	        	}
	        	

        	}
        	

        }

        echo '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
		exit();	

	}




}