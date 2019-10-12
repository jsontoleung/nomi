<?php 
namespace app\common\config;
use think\Db;
use Log;


/**
 * 商品支付回调
 */
class ProductAllot {


	/**
	 * @author 会员等级相应分配
	 * @param $memberpay 订单信息
	 * @param $user 当前用户
	 * @param $topUser 上级用户
	 * @param $parentUser 上上级用户
	 * @param $proInfo 商品信息
	 * @param $total_fee 支付回调金额
	 */
	public static function moneyReturn ($orderMaster, $user, $topUser, $parentUser, $proInfo, $total_fee) {
			

		if (!empty($orderMaster)) {
        

        	if (empty($topUser)) {// 没有上级

        		Db::startTrans();
        		try {

        			// 购买该商品直接升级当前会员
        			$returnInt = self::saveUser($proInfo, $user, $total_fee, $orderMaster);

        			// 商品企业体系返佣
        			$saveRetail = self::vipRetail($orderMaster, $proInfo, 1);

        			// 订单回调更改
		        	$data['payment_money'] = floatval($total_fee / 100);
			        $data['pay_time'] = time();
			        $data['update_time'] = time();
			        $data['pay_status'] = 1;
			        $data['order_status'] = 1;
			        $save = model('orderMaster')->save($data, ['order_sn' => $orderMaster['order_sn']]);
			        if (!$save) {
			        	Log::write('订单回调---失败');
			        }

			        if ($returnInt && $saveRetail && $save) {
				       	Db::commit();
			        	Log::write('数据和记录都成功');
			        } else {
			        	Log::write('没有上级---失败');
			        }

        			
        		} catch (Exception $e) {
        			Db::rollback();
	            	throw $e;
        		}
	        		
        		
        	} else {

        		

        		if (empty($parentUser)) {// 有上级，没有上上级

        			Db::startTrans();
	        		try {

	        			// 购买该商品直接升级当前会员
	        			$returnInt = self::saveUser($proInfo, $user, $total_fee, $orderMaster);

	        			// 商品企业体系返佣
	        			$saveRetail = self::vipRetail($orderMaster, $proInfo, 2);

	        			// 上级返佣
	        			$top['return_money'] = $topUser['return_money'] + $proInfo['give_two'];// 现金返现
	        			$top['team_integral'] = $topUser['team_integral'] + round($total_fee, 0);// 积分返佣
	        			$topSave = model('user')->save($top, ['user_id' => $topUser['user_id']]);
	        			if (!$topSave) {
				        	Log::write('上级返佣---失败');
				        }
		        		
		        		// 订单回调更改
			        	$data['payment_money'] = floatval($total_fee / 100);
			        	$data['give_two'] = $proInfo['give_two'];
				        $data['pay_time'] = time();
			        	$data['update_time'] = time();
				        $data['pay_status'] = 1;
				        $data['order_status'] = 1;
				        $save = model('orderMaster')->save($data, ['order_sn' => $orderMaster['order_sn']]);
				        if (!$save) {
				        	Log::write('订单回调---失败');
				        }

				        if ($returnInt && $saveRetail && $topSave && $save) {
				        	Db::commit();
				        	Log::write('数据和记录都成功');
				        } else {
				        	Log::write('有上级，没有上上级失败');
				        }

	        			
	        		} catch (Exception $e) {
	        			Db::rollback();
	            		throw $e;
	        		}
        			

        		} else {// 有上级和上上级

        			Db::startTrans();
        			try {

        				// 购买该商品直接升级当前会员
	        			$returnInt = self::saveUser($proInfo, $user, $total_fee, $orderMaster);

	        			// 商品企业体系返佣
	        			$saveRetail = self::vipRetail($orderMaster, $proInfo, 3);

        				// 上级返佣
	        			$top['return_money'] = $topUser['return_money'] + $proInfo['give_two'];// 现金返现
	        			$top['team_integral'] = $topUser['team_integral'] + round($total_fee, 0);// 积分返佣
	        			$topSave = model('user')->save($top, ['user_id' => $topUser['user_id']]);
	        			if (!$topSave) {
				        	Log::write('订单回调---失败');
				        }

	        			// 上上级返佣
	        			$parent['return_money'] = $parentUser['return_money'] + $proInfo['give_one'];// 现金返现
	        			$parent['team_integral'] = $parentUser['team_integral'] + round($total_fee, 0);// 积分返佣
	        			$parentSave = model('user')->save($parent, ['user_id' => $parentUser['user_id']]);
	        			if (!$parentSave) {
				        	Log::write('订单回调---失败');
				        }
		        		
		        		// 订单回调更改
			        	$data['payment_money'] = floatval($total_fee / 100);
			        	$data['give_two'] = $proInfo['give_two'];
			        	$data['give_one'] = $proInfo['give_one'];
				        $data['pay_time'] = time();
			        	$data['update_time'] = time();
				        $data['pay_status'] = 1;
				        $data['order_status'] = 1;
				        $save = model('orderMaster')->save($data, ['order_sn' => $orderMaster['order_sn']]);
				        if (!$save) {
				        	Log::write('订单回调---失败');
				        }

				        if ($returnInt && $saveRetail && $topSave && $parentSave && $save) {
				        	Db::commit();
				        	Log::write('数据和记录都成功');
				        } else {
				        	Log::write('有上级和上上级失败');
				        }
        				
        			} catch (Exception $e) {
        				Db::rollback();
            			throw $e;
        			}

        		}

        	}

        	
        } else {
        	Log::write('订单信息不存在');
        }




	}// ----end




	/**
	 * 用户积分或升级会员
	 */
	private static function saveUser ($proInfo, $user, $total_fee, $orderMaster) {

		// 购买该商品直接升级当前会员
        if ( ($proInfo['is_member'] == 1) && ($proInfo['level'] > $user['level']) ) {

        	// 购买用户积分返佣
    		$res['integral'] = $user['integral'] + round($total_fee, 0);
    		$res['level'] = $proInfo['level'];
    		$returnInt = model('user')->save($res, ['user_id' => $orderMaster['uid']]);
    		if (!$returnInt) {
    			Log::write('购买该商品直接升级当前会员--失败');
    		}

        } else {

        	// 购买用户积分返佣
    		$res['integral'] = $user['integral'] + round($total_fee, 0);
    		$returnInt = model('user')->save($res, ['user_id' => $orderMaster['uid']]);
    		if (!$returnInt) {
    			Log::write('购买该商品直接升级当前会员--失败');
    		}

        }
        return $returnInt;

	}





	/**
	 * 商品企业体系返佣
	 * @param $orderMaster 订单信息（array）
	 * @param $proInfo 商品信息
	 * @param $status 状态：1 顶级、2 有上级没有上上级、3 有上级和上上级
	 */
	private static function vipRetail ($orderMaster, $proInfo, $status = 1) {

		// 商品池分配按---->会员分配
		$userLevel = model('Userlevel')->where('level_id=:id', ['id' => 3])->find();
		if (empty($userLevel)) {
			Log::write('获取池分配信息--失败');
		}

	    // 企业体系返佣记录信息
	    $dataRetail['order_id'] = $orderMaster['order_id'];
	    $dataRetail['retail_id'] = $orderMaster['retail_id'];
	    $dataRetail['c_pond'] = $userLevel['c_pond'];
	    $dataRetail['b_pond'] = $userLevel['b_pond'];
	    $dataRetail['a_pond'] = $userLevel['a_pond'];
	    $dataRetail['r_pond'] = $userLevel['r_pond'];
	    $dataRetail['add_time'] = time();

	    // 商品池返佣全有的情况(H、K、S)T池按----P池5%、H池10%、K池20%、S池10%、Z池20%、G池20%、N池15%，进行分配
	    if (!empty($orderMaster['h_pond']) && !empty($orderMaster['k_pond']) && !empty($orderMaster['s_pond'])) {

	        // 顶级
	        if ($status == 1) {
	            // T池总数 没有上级，加起来
	            $tCount = $userLevel['t_pond'] + $proInfo['give_two'];
	            $dataRetail['n_pond'] = $userLevel['n_pond'] + $proInfo['give_one'] + round($tCount * 0.15, 2);

	        // 有上级，没有上上级
	        } elseif ($status == 2) {
	            // T池总数
	            $tCount = $userLevel['t_pond'];
	            $dataRetail['n_pond'] = $userLevel['n_pond'] + $proInfo['give_one'] + round($tCount * 0.15, 2);

	        // 全有
	        } elseif ($status == 3) {
	            // T池总数 加起来
	            $tCount = $userLevel['t_pond'];
	            $dataRetail['n_pond'] = $userLevel['n_pond'] + round($tCount * 0.15, 2);
	        } else {
	        	Log::write('状态--失败');
	        }
	        

	        // 企业体系返佣记录信息
	        $dataRetail['h_pond'] = $userLevel['h_pond'] + round($tCount * 0.1, 2);
	        $dataRetail['s_pond'] = $userLevel['s_pond'] + round($tCount * 0.1, 2);
	        $dataRetail['z_pond'] = $userLevel['z_pond'] + round($tCount * 0.2, 2);
	        $dataRetail['g_pond'] = $userLevel['g_pond'] + round($tCount * 0.2, 2);
	        $dataRetail['p_pond'] = $userLevel['p_pond'] + round($tCount * 0.05, 2);
	        $dataRetail['k_pond'] = $userLevel['k_pond'] + round($tCount * 0.2, 2);
	        // 插入数据
	        $saveRetail = model('OrderRetail')->insert($dataRetail);
	        if (!$saveRetail) {
	        	Log::write('企业体系返佣记录信息--失败');
	        }
	        

	    // 商品池返佣(H 无的情况)T池按 ----P池5%、H池10%、K池20%、S池10%、Z池20%、G池20%、N池15%，进行分配
	    // 计算 H池的10% / 其他池的加起来的总数，分配其他池
	    } elseif (empty($orderMaster['h_pond']) && !empty($orderMaster['k_pond']) && !empty($orderMaster['s_pond'])) {

	        if ($status == 1) {
	            // T池总数 没有上级，没有H池，加起来
	            $tCount = $userLevel['t_pond'] + $proInfo['give_two'] + $userLevel['h_pond'];
	            $dataRetail['n_pond'] = $userLevel['n_pond'] + $proInfo['give_one'] + round($tCount * (0.15+$hScale), 2);
	        } elseif ($status == 2) {
	            // T池总数 没有H池，加起来
	            $tCount = $userLevel['t_pond'] + $userLevel['h_pond'];
	            $dataRetail['n_pond'] = $userLevel['n_pond'] + $proInfo['give_one'] + round($tCount * (0.15+$hScale), 2);
	        } elseif ($status == 3) {
	            // T池总数 没有H池，加起来
	            $tCount = $userLevel['t_pond'] + $userLevel['h_pond'];
	            $dataRetail['n_pond'] = $userLevel['n_pond'] + round($tCount * (0.15+$hScale), 2);
	        }

	        // H池比例
            $hScale = round(0.1 / 6, 2);

            $dataRetail['p_pond'] = $userLevel['p_pond'] + round($tCount * (0.05+$hScale), 2);
            $dataRetail['k_pond'] = $userLevel['k_pond'] + round($tCount * (0.2+$hScale), 2);
            $dataRetail['s_pond'] = $userLevel['s_pond'] + round($tCount * (0.1+$hScale), 2);
            $dataRetail['z_pond'] = $userLevel['z_pond'] + round($tCount * (0.2+$hScale), 2);
            $dataRetail['g_pond'] = $userLevel['g_pond'] + round($tCount * (0.2+$hScale), 2);
	        // 插入数据
	        $saveRetail = model('OrderRetail')->insert($dataRetail);
	        if (!$saveRetail) {
	        	Log::write('企业体系返佣记录信息--失败');
	        }
	        
	    
	    // 商品池返佣(H和K 无的情况)T池按 ----P池5%、H池10%、K池20%、S池10%、Z池20%、G池20%、N池15%，进行分配
	    // 计算 H池的10% + K池的20% / 其他池的加起来的总数，分配其他池
	    } elseif (empty($orderMaster['h_pond']) && empty($orderMaster['k_pond']) && !empty($orderMaster['s_pond'])) {

	        if ($status == 1) {
	            // T池总数 没有上级，没有H池，没有K池，加起来
	            $tCount = $userLevel['t_pond'] + $proInfo['give_two'] + $userLevel['h_pond'] + $userLevel['k_pond'];
	            $dataRetail['n_pond'] = $userLevel['n_pond'] + $proInfo['give_one'] + round($tCount * (0.15+$hScale), 2);
	        } elseif ($status == 2) {
	            // T池总数 没有H池，没有K池，加起来
	            $tCount = $userLevel['t_pond'] + $userLevel['h_pond'] + $userLevel['k_pond'];
	            $dataRetail['n_pond'] = $userLevel['n_pond'] + $proInfo['give_one'] + round($tCount * (0.15+$hScale), 2);
	        } elseif ($status == 3) {
	            // T池总数 没有上级，没有H池，没有K池，加起来
	            $tCount = $userLevel['t_pond'] + $userLevel['h_pond'] + $userLevel['k_pond'];
	            $dataRetail['n_pond'] = $userLevel['n_pond'] + round($tCount * (0.15+$hScale), 2);
	        }
	        
	        // H和K池比例
            $hScale = round(0.1 / 5, 2);

            $dataRetail['p_pond'] = $userLevel['p_pond'] + round($tCount * (0.05+$hScale), 2);
            $dataRetail['s_pond'] = $userLevel['s_pond'] + round($tCount * (0.1+$hScale), 2);
            $dataRetail['z_pond'] = $userLevel['z_pond'] + round($tCount * (0.2+$hScale), 2);
            $dataRetail['g_pond'] = $userLevel['g_pond'] + round($tCount * (0.2+$hScale), 2);
	        // 插入数据
	        $saveRetail = model('OrderRetail')->insert($dataRetail);
	        if (!$saveRetail) {
	        	Log::write('企业体系返佣记录信息--失败');
	        }


	    // 商品池返佣(全无的情况)T池按 ----P池5%、H池10%、K池20%、S池10%、Z池20%、G池20%、N池15%，进行分配
	    // 计算 H池的10% + K池的20% + S池的10% / 其他池的加起来的总数，分配其他池
	    } elseif (empty($orderMaster['h_pond']) && empty($orderMaster['k_pond']) && empty($orderMaster['s_pond'])) {

	        if ($status == 1) {
	            // T池总数 没有上级，没有H池，没有K池，没有S池，加起来
	            $tCount = $userLevel['t_pond'] + $proInfo['give_two'] + $userLevel['h_pond'] + $userLevel['k_pond'] + $userLevel['s_pond'];
	            $dataRetail['n_pond'] = $userLevel['n_pond'] + $proInfo['give_one'] + round($tCount * (0.15+$hScale), 2);
	        } elseif ($status == 2) {
	            // T池总数 没有上级，没有H池，没有K池，没有S池，加起来
	            $tCount = $userLevel['t_pond'] + $userLevel['h_pond'] + $userLevel['k_pond'] + $userLevel['s_pond'];
	            $dataRetail['n_pond'] = $userLevel['n_pond'] + $proInfo['give_one'] + round($tCount * (0.15+$hScale), 2);
	        } elseif ($status == 3) {
	            // T池总数 没有上级，没有H池，没有K池，没有S池，加起来
	            $tCount = $userLevel['t_pond'] + $userLevel['h_pond'] + $userLevel['k_pond'] + $userLevel['s_pond'];
	            $dataRetail['n_pond'] = $userLevel['n_pond'] + round($tCount * (0.15+$hScale), 2);
	        }
	        // H和K池比例
            $hScale = round(0.1 / 4, 2);

            $dataRetail['p_pond'] = $userLevel['p_pond'] + round($tCount * (0.05+$hScale), 2);
            $dataRetail['z_pond'] = $userLevel['z_pond'] + round($tCount * (0.2+$hScale), 2);
            $dataRetail['g_pond'] = $userLevel['g_pond'] + round($tCount * (0.2+$hScale), 2);
	        // 插入数据
	        $saveRetail = model('OrderRetail')->insert($dataRetail);
	        if (!$saveRetail) {
	        	Log::write('企业体系返佣记录信息--失败');
	        }

	    } else {
	    	Log::write('商品池返佣--失败');
	    }
	    return $saveRetail;

	}//---end



}
