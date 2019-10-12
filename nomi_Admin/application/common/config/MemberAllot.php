<?php 
namespace app\common\config;
use think\Db;
use Log;


/**
 * 会员等级回调
 */
class MemberAllot {


	/**
	 * @author 会员等级相应分配
	 * @param $memberpay 订单信息和分销商
	 * @param $total_fee 购买金额
	 * @param $userLevel 购买会员等级信息
	 * @param $secondUser 查找二级
	 * @param $oneUser 查询一级
	 * @param $dataUser 购买用户信息
	 */
	public static function moneyReturn ($memberpay, $total_fee, $userLevel, $secondUser, $oneUser, $dataUser) {


		// 购买会员订单信息
		$data['payment_method'] = 5;
		$data['payment_money'] = floatval($total_fee / 100);
        $data['pay_time'] = time();
        $data['order_integral'] = $total_fee;
        $data['pay_status'] = 1;
        $data['order_status'] = 2;

		if (empty($memberpay['level_id'])) {

			Log::write('购买用户等级信息为空');

		// 购买体验会员
		} elseif ($memberpay['level_id'] == 2) {


			// 顶级用户
			if (empty($secondUser)) {
				

				Db::startTrans();
				try {

					$dataRetail['order_id'] = $memberpay['order_id'];
				    $dataRetail['retail_id'] = $memberpay['retail_id'];
				    $dataRetail['add_time'] = time();

					// 无S池
					if ($memberpay['s_pond'] == '') {

						// S池的返佣值
						$countPond = userLevel['s_pond'] + $userLevel['second_level'];

						// 企业体系返佣记录信息
				        $dataRetail['p_pond'] = round(($countPond * 0.4), 2) + $userLevel['p_pond'] + $userLevel['one_level'];
				        $dataRetail['z_pond'] = round(($countPond * 0.2), 2) + $userLevel['z_pond'];
				        $dataRetail['g_pond'] = round(($countPond * 0.2), 2) + $userLevel['g_pond'];
				        $dataRetail['n_pond'] = round(($countPond * 0.2), 2) + $userLevel['n_pond'];

				    } else {

				    	// 企业体系返佣记录信息
				        $dataRetail['s_pond'] = $userLevel['second_level'] + $userLevel['s_pond'];
				        $dataRetail['p_pond'] = $userLevel['one_level'] + $userLevel['p_pond'];
				        $dataRetail['z_pond'] = $userLevel['z_pond'];
				        $dataRetail['g_pond'] = $userLevel['g_pond'];

				    }
				    // 插入数据
				    $saveRetail = model('MemberRetail')->insert($dataRetail);

				    // 购买会员订单信息
			        $memberpaySave = model('MemberOrder')->save($data, ['order_id' => $memberpay['order_id']]);

			        // 用户购买之后插入数据
    				$levelSave = model('user')->save($dataUser, ['user_id' => $memberpay['uid']]);

			        if ($saveRetail && $memberpaySave && $levelSave) {
			        	Db::commit();
        				Log::write('数据和记录都成功');
			        } else {
			        	Log::write('体现会员:顶级--没有S池数据或记录失败');
			        }


			    } catch (Exception $e) {
					Db::rollback();
	       			throw $e;
				}
				


			// 有上级，无上上级
			} elseif (!empty($secondUser) && empty($oneUser)) {
					
				Db::startTrans();
				try {

					// 企业体系返佣记录信息
					$dataRetail['order_id'] = $memberpay['order_id'];
			        $dataRetail['retail_id'] = $memberpay['retail_id'];

					// 无S池
					if ($memberpay['s_pond'] == '') {

						// S池的返佣值
						$countPond = userLevel['s_pond'];

						// 企业体系返佣记录信息
				        $dataRetail['p_pond'] = round(($countPond * 0.4), 2) + $userLevel['p_pond'] + $userLevel['one_level'];
				        $dataRetail['z_pond'] = round(($countPond * 0.2), 2) + $userLevel['z_pond'];
				        $dataRetail['g_pond'] = round(($countPond * 0.2), 2) + $userLevel['g_pond'];
				        $dataRetail['n_pond'] = round(($countPond * 0.2), 2) + $userLevel['n_pond'];

				   	} else {

				   		// 企业体系返佣记录信息
				        $dataRetail['p_pond'] = $userLevel['p_pond'] + $userLevel['one_level'];
				        $dataRetail['s_pond'] = $userLevel['s_pond'];
				        $dataRetail['z_pond'] = $userLevel['z_pond'];
				        $dataRetail['g_pond'] = $userLevel['g_pond'];

				   	}
				   	// 插入数据
				    $saveRetail = model('MemberRetail')->insert($dataRetail);

				   	// 上级收益
			        $secondData['return_money'] = $secondUser['return_money'] + $userLevel['second_level'];
	        		$secondData['team_integral'] = round(($secondUser['team_integral'] + $total_fee), 0);
	        		$saveSecond = model('User')->save($secondData, ['user_id' => $secondUser['user_id']]);

	        		// 购买会员订单信息
			        $data['second_uid'] = $secondUser['user_id'];
			        $data['second_level_money'] = $userLevel['second_level'];
			        $data['second_level_integral'] = round($total_fee, 0);
			        $memberpaySave = model('MemberOrder')->save($data, ['order_id' => $memberpay['order_id']]);

			        // 用户购买之后插入数据
    				$levelSave = model('user')->save($dataUser, ['user_id' => $memberpay['uid']]);

			        if ($saveRetail && $saveSecond && $memberpaySave && $levelSave) {
			        	Db::commit();
        				Log::write('数据和记录都成功');
			        } else {
			        	Log::write('体现会员:有上级，无上上级--没有S池数据或记录失败');
			        }

					
				} catch (Exception $e) {
					Db::rollback();
	       			throw $e;
				}



			// 有上级和上上级
			} else {


				Db::startTrans();
				try {

					// 无S池
					if ($memberpay['s_pond'] == '') {

						// S池的返佣值
						$countPond = userLevel['s_pond'];

						// 企业体系返佣记录信息
						$dataRetail['order_id'] = $memberpay['order_id'];
				        $dataRetail['retail_id'] = $memberpay['retail_id'];
				        $dataRetail['p_pond'] = round(($countPond * 0.4), 2) + $userLevel['p_pond'];
				        $dataRetail['z_pond'] = round(($countPond * 0.2), 2) + $userLevel['z_pond'];
				        $dataRetail['g_pond'] = round(($countPond * 0.2), 2) + $userLevel['g_pond'];
				        $dataRetail['n_pond'] = round(($countPond * 0.2), 2) + $userLevel['n_pond'];

			    	} else {

			    		// 企业体系返佣记录信息
						$dataRetail['order_id'] = $memberpay['order_id'];
				        $dataRetail['retail_id'] = $memberpay['retail_id'];
				        $dataRetail['p_pond'] = $userLevel['p_pond'];
				        $dataRetail['s_pond'] = $userLevel['s_pond'];
				        $dataRetail['z_pond'] = $userLevel['z_pond'];
				        $dataRetail['g_pond'] = $userLevel['g_pond'];

			    	}
			    	// 插入数据
					$saveRetail = model('MemberRetail')->insert($dataRetail);


			    	// 上级收益
			        $secondData['return_money'] = $secondUser['return_money'] + $userLevel['second_level'];
	        		$secondData['team_integral'] = round(($secondUser['team_integral'] + $total_fee), 0);
	        		$saveSecond = model('User')->save($secondData, ['user_id' => $secondUser['user_id']]);

	        		// 上上级收益
	        		$oneData['return_money'] = $oneUser['return_money'] + $userLevel['one_level'];
	        		$oneData['team_integral'] = round($oneUser['team_integral'] + $total_fee, 0);
	        		$saveOne = model('User')->save($oneData, ['user_id' => $oneUser['user_id']]);

	        		// 购买会员订单信息
			        $data['second_uid'] = $secondUser['user_id'];
			        $data['second_level_money'] = $userLevel['second_level'];
			        $data['second_level_integral'] = round($total_fee, 0);
			        $data['one_uid'] = $oneUser['user_id'];
			        $data['one_level_money'] = $userLevel['one_level'];
			        $data['one_level_integral'] = round($total_fee, 0);
			        $memberpaySave = model('MemberOrder')->save($data, ['order_id' => $memberpay['order_id']]);

			        // 用户购买之后插入数据
    				$levelSave = model('user')->save($dataUser, ['user_id' => $memberpay['uid']]);

			        if ($saveRetail && $saveSecond && $saveOne && $memberpaySave && $levelSave) {
			        	Db::commit();
        				Log::write('数据和记录都成功');
			        } else {
			        	Log::write('体现会员:有上级，有上上级--没有S池数据或记录失败');
			        }
					
				} catch (Exception $e) {
					Db::rollback();
	       			throw $e;
				}



			}
				



		// 会员 --- VIP --合伙人
		} elseif (($memberpay['level_id'] == 3) || ($memberpay['level_id'] == 4) || ($memberpay['level_id'] == 5)) {


	        // 顶级用户
	        if (empty($secondUser)) {

	        	Db::startTrans();
	        	try {

	        		// 会员、VIP、合伙人企业体系返佣
	        		$saveRetail = self::vipRetail($memberpay, $userLevel, 1);


	        		// 购买会员订单信息
					$memberpaySave = model('MemberOrder')->save($data, ['order_id' => $memberpay['order_id']]);

					// 用户购买之后插入数据
	    			$levelSave = model('user')->save($dataUser, ['user_id' => $memberpay['uid']]);

					if ($saveRetail && $memberpaySave && $levelSave) {
			        	Db::commit();
	    				Log::write('数据和记录都成功');
			        } else {
			        	Log::write('会员:顶级--数据或记录失败');
			        }
	        		
		        	

			    } catch (Exception $e) {
	        		Db::rollback();
		       		throw $e;
	        	}


	        // 有上级，没有上上级
	        } elseif (!empty($secondUser) && empty($oneUser)) {

	        	Db::startTrans();
	        	try {

	        		// 会员、VIP、合伙人企业体系返佣
	        		$saveRetail = self::vipRetail($memberpay, $userLevel, 2);


			        // 上级收益
			        $secondData['return_money'] = $secondUser['return_money'] + $userLevel['second_level'];
	        		$secondData['team_integral'] = round(($secondUser['team_integral'] + $total_fee), 0);
	        		$saveSecond = model('User')->save($secondData, ['user_id' => $secondUser['user_id']]);

			        // 购买会员订单信息
			        $data['second_uid'] = $secondUser['user_id'];
			        $data['second_level_money'] = $userLevel['second_level'];
			        $data['second_level_integral'] = round($total_fee, 0);
					$memberpaySave = model('MemberOrder')->save($data, ['order_id' => $memberpay['order_id']]);

					// 用户购买之后插入数据
	    			$levelSave = model('user')->save($dataUser, ['user_id' => $memberpay['uid']]);

					if ($saveRetail && $saveSecond && $memberpaySave && $levelSave) {
			        	Db::commit();
	    				Log::write('数据和记录都成功');
			        } else {
			        	Log::write('会员:有上级，没有上上级--数据或记录失败');
			        }

			    } catch (Exception $e) {
	        		Db::rollback();
		       		throw $e;
	        	}


	        // 有上级和上上级
	        } elseif (!empty($secondUser) && !empty($oneUser)) {

	        	Db::startTrans();
	        	try {
	        		
		        	// 会员、VIP、合伙人企业体系返佣
	        		$saveRetail = self::vipRetail($memberpay, $userLevel, 3);

			        // 上级收益
			        $secondData['return_money'] = $secondUser['return_money'] + $userLevel['second_level'];
	        		$secondData['team_integral'] = round(($secondUser['team_integral'] + $total_fee), 0);
	        		$saveSecond = model('User')->save($secondData, ['user_id' => $secondUser['user_id']]);

			        // 上上级收益
	        		$oneData['return_money'] = $oneUser['return_money'] + $userLevel['one_level'];
	        		$oneData['team_integral'] = round($oneUser['team_integral'] + $total_fee, 0);
	        		$saveOne = model('User')->save($oneData, ['user_id' => $oneUser['user_id']]);

	        		// 购买会员订单信息
			        $data['second_uid'] = $secondUser['user_id'];
			        $data['second_level_money'] = $userLevel['second_level'];
			        $data['second_level_integral'] = round($total_fee, 0);
			        $data['one_uid'] = $oneUser['user_id'];
			        $data['one_level_money'] = $userLevel['one_level'];
			        $data['one_level_integral'] = round($total_fee, 0);
			        $memberpaySave = model('MemberOrder')->save($data, ['order_id' => $memberpay['order_id']]);

			        // 用户购买之后插入数据
	    			$levelSave = model('user')->save($dataUser, ['user_id' => $memberpay['uid']]);

					if ($saveRetail && $saveSecond && $saveOne && $memberpaySave && $levelSave) {
			        	Db::commit();
	    				Log::write('数据和记录都成功');
			        } else {
			        	Log::write('会员:有上级，没有上上级--数据或记录失败');
			        }

			    } catch (Exception $e) {
	        		Db::rollback();
		       		throw $e;
	        	}


	        } else {
	        	Log::write('上级和上上级数据错误');
	        }




		}

		 

		

	}//---end





	/**
	 * 会员、VIP、合伙人企业体系返佣
	 * @param $memberpay 订单信息和分销商
	 * @param $userLevel 购买会员等级信息
	 * @param $status 状态：1 顶级、2 有上级没有上上级、3 有上级和上上级
	 */
	private static function vipRetail ($memberpay, $userLevel, $status = 1) {

	    // 企业体系返佣记录信息
	    $dataRetail['order_id'] = $memberpay['order_id'];
	    $dataRetail['retail_id'] = $memberpay['retail_id'];
	    $dataRetail['c_pond'] = $userLevel['c_pond'];
	    $dataRetail['b_pond'] = $userLevel['b_pond'];
	    $dataRetail['a_pond'] = $userLevel['a_pond'];
	    $dataRetail['r_pond'] = $userLevel['r_pond'];
	    $dataRetail['add_time'] = time();

	    // 会员和VIP全有的情况(H、K、S)T池按----P池5%、H池10%、K池20%、S池10%、Z池20%、G池20%、N池15%，进行分配
	    // 合伙人全有的情况(H、K、S)T池按----按H池10%、K池25%、S池10%、Z池20%、G池20%、N池15%
	    if (!empty($memberpay['h_pond']) && !empty($memberpay['k_pond']) && !empty($memberpay['s_pond'])) {

	        // 顶级
	        if ($status == 1) {
	            // T池总数 没有上级，加起来
	            $tCount = $userLevel['t_pond'] + $userLevel['second_level'];
	        	$dataRetail['n_pond'] = $userLevel['n_pond'] + $userLevel['one_level'] + round($tCount * 0.15, 2);


	        // 有上级，没有上上级
	        } elseif ($status == 2) {
	            // T池总数
	            $tCount = $userLevel['t_pond'];
	            $dataRetail['n_pond'] = $userLevel['n_pond'] + $userLevel['one_level'] + round($tCount * 0.15, 2);

	        // 全有
	        } elseif ($status == 3) {
	            // T池总数 加起来
	            $tCount = $userLevel['t_pond'];
	            $dataRetail['n_pond'] = $userLevel['n_pond'] + round($tCount * 0.15, 2);
	        }
	        

	        // 企业体系返佣记录信息
	        $dataRetail['h_pond'] = $userLevel['h_pond'] + round($tCount * 0.1, 2);
	        $dataRetail['s_pond'] = $userLevel['s_pond'] + round($tCount * 0.1, 2);
	        $dataRetail['z_pond'] = $userLevel['z_pond'] + round($tCount * 0.2, 2);
	        $dataRetail['g_pond'] = $userLevel['g_pond'] + round($tCount * 0.2, 2);

	        // 会员和VIP
	        if ($memberpay['level_id'] == 3 || $memberpay['level_id'] == 4) {
	            
	            $dataRetail['p_pond'] = $userLevel['p_pond'] + round($tCount * 0.05, 2);
	            $dataRetail['k_pond'] = $userLevel['k_pond'] + round($tCount * 0.2, 2);

	        // 合伙人
	        } elseif ($memberpay['level_id'] == 5) {

	            $dataRetail['k_pond'] = $userLevel['k_pond'] + round($tCount * 0.25, 2);

	        } else {
	            Log::write('超过等级返佣不了');
	        }
	        // 插入数据
	        $saveRetail = model('MemberRetail')->insert($dataRetail);
	        

	    // 会员和VIP(H 无的情况)T池按 ----P池5%、H池10%、K池20%、S池10%、Z池20%、G池20%、N池15%，进行分配
	    // 合伙人(H 无的情况)T池按    ----H池10%、K池25%、S池10%、Z池20%、G池20%、N池15%
	    // 计算 H池的10% / 其他池的加起来的总数，分配其他池
	    } elseif (empty($memberpay['h_pond']) && !empty($memberpay['k_pond']) && !empty($memberpay['s_pond'])) {

	        if ($status == 1) {
	            // T池总数 没有上级，没有H池，加起来
	            $tCount = $userLevel['t_pond'] + $proInfo['give_two'] + $userLevel['h_pond'];
	            $dataRetail['n_pond'] = $userLevel['n_pond'] + $userLevel['one_level'] + round($tCount * (0.15+$hScale), 2);
	        } elseif ($status == 2) {
	            // T池总数 没有H池，加起来
	            $tCount = $userLevel['t_pond'] + $userLevel['h_pond'];
	            $dataRetail['n_pond'] = $userLevel['n_pond'] + $userLevel['one_level'] + round($tCount * (0.15+$hScale), 2);
	        } elseif ($status == 3) {
	            // T池总数 没有上级，没有H池，加起来
	            $tCount = $userLevel['t_pond'] + $userLevel['h_pond'];
	            $dataRetail['n_pond'] = $userLevel['n_pond'] + round($tCount * (0.15+$hScale), 2);
	        }


	        // 会员和VIP
	        if ($memberpay['level_id'] == 3 || $memberpay['level_id'] == 4) {

	            // H池比例
	            $hScale = round(0.1 / 6, 2);

	            $dataRetail['p_pond'] = $userLevel['p_pond'] + round($tCount * (0.05+$hScale), 2);
	            $dataRetail['k_pond'] = $userLevel['k_pond'] + round($tCount * (0.2+$hScale), 2);
	            $dataRetail['s_pond'] = $userLevel['s_pond'] + round($tCount * (0.1+$hScale), 2);
	            $dataRetail['z_pond'] = $userLevel['z_pond'] + round($tCount * (0.2+$hScale), 2);
	            $dataRetail['g_pond'] = $userLevel['g_pond'] + round($tCount * (0.2+$hScale), 2);

	        // 合伙人
	        } elseif ($memberpay['level_id'] == 5) {

	            // H池比例
	            $hScale = round(0.1 / 5, 2);

	            $dataRetail['k_pond'] = $userLevel['k_pond'] + round($tCount * (0.25+$hScale), 2);
	            $dataRetail['s_pond'] = $userLevel['s_pond'] + round($tCount * (0.1+$hScale), 2);
	            $dataRetail['z_pond'] = $userLevel['z_pond'] + round($tCount * (0.2+$hScale), 2);
	            $dataRetail['g_pond'] = $userLevel['g_pond'] + round($tCount * (0.2+$hScale), 2);

	        } else {
	            Log::write('超过等级返佣不了');
	        }
	        // 插入数据
	        $saveRetail = model('MemberRetail')->insert($dataRetail);
	        
	    
	    // 会员和VIP(H和K 无的情况)T池按 ----P池5%、H池10%、K池20%、S池10%、Z池20%、G池20%、N池15%，进行分配
	    // 合伙人(H和K 无的情况)T池按    ----H池10%、K池25%、S池10%、Z池20%、G池20%、N池15%
	    // 计算 H池的10% + K池的20% / 其他池的加起来的总数，分配其他池
	    } elseif (empty($memberpay['h_pond']) && empty($memberpay['k_pond']) && !empty($memberpay['s_pond'])) {

	        if ($status == 1) {
	            // T池总数 没有上级，没有H池，没有K池，加起来
	            $tCount = $userLevel['t_pond'] + $userLevel['second_level'] + $userLevel['h_pond'] + $userLevel['k_pond'];
	            $dataRetail['n_pond'] = $userLevel['n_pond'] + $userLevel['one_level'] + round($tCount * (0.15+$hScale), 2);
	        } elseif ($status == 2) {
	            // T池总数 没有H池，没有K池，加起来
	            $tCount = $userLevel['t_pond'] + $userLevel['h_pond'] + $userLevel['k_pond'];
	            $dataRetail['n_pond'] = $userLevel['n_pond'] + $userLevel['one_level'] + round($tCount * (0.15+$hScale), 2);
	        } elseif ($status == 3) {
	            // T池总数 没有上级，没有H池，没有K池，加起来
	            $tCount = $userLevel['t_pond'] + $userLevel['h_pond'] + $userLevel['k_pond'];
	            $dataRetail['n_pond'] = $userLevel['n_pond'] + round($tCount * (0.15+$hScale), 2);
	        }
	        

	        // 会员和VIP
	        if ($memberpay['level_id'] == 3 || $memberpay['level_id'] == 4) {

	            // H和K池比例
	            $hScale = round(0.1 / 5, 2);

	            $dataRetail['p_pond'] = $userLevel['p_pond'] + round($tCount * (0.05+$hScale), 2);
	            $dataRetail['s_pond'] = $userLevel['s_pond'] + round($tCount * (0.1+$hScale), 2);
	            $dataRetail['z_pond'] = $userLevel['z_pond'] + round($tCount * (0.2+$hScale), 2);
	            $dataRetail['g_pond'] = $userLevel['g_pond'] + round($tCount * (0.2+$hScale), 2);

	        // 合伙人
	        } elseif ($memberpay['level_id'] == 5) {

	            // H和K池比例
	            $hScale = round(0.1 / 4, 2);

	            $dataRetail['s_pond'] = $userLevel['s_pond'] + round($tCount * (0.1+$hScale), 2);
	            $dataRetail['z_pond'] = $userLevel['z_pond'] + round($tCount * (0.2+$hScale), 2);
	            $dataRetail['g_pond'] = $userLevel['g_pond'] + round($tCount * (0.2+$hScale), 2);

	        } else {
	            Log::write('超过等级返佣不了');
	        }
	        // 插入数据
	        $saveRetail = model('MemberRetail')->insert($dataRetail);


	    // 会员和VIP(全无的情况)T池按 ----P池5%、H池10%、K池20%、S池10%、Z池20%、G池20%、N池15%，进行分配
	    // 合伙人(全无的情况)T池按    ----H池10%、K池25%、S池10%、Z池20%、G池20%、N池15%
	    // 计算 H池的10% + K池的20% + S池的10% / 其他池的加起来的总数，分配其他池
	    } elseif (empty($memberpay['h_pond']) && empty($memberpay['k_pond']) && empty($memberpay['s_pond'])) {

	        if ($status == 1) {
	            // T池总数 没有上级，没有H池，没有K池，没有S池，加起来
	            $tCount = $userLevel['t_pond'] + $userLevel['second_level'] + $userLevel['h_pond'] + $userLevel['k_pond'] + $userLevel['s_pond'];
	            $dataRetail['n_pond'] = $userLevel['n_pond'] + $userLevel['one_level'] + round($tCount * (0.15+$hScale), 2);
	        } elseif ($status == 2) {
	            // T池总数 没有上级，没有H池，没有K池，没有S池，加起来
	            $tCount = $userLevel['t_pond'] + $userLevel['h_pond'] + $userLevel['k_pond'] + $userLevel['s_pond'];
	            $dataRetail['n_pond'] = $userLevel['n_pond'] + $userLevel['one_level'] + round($tCount * (0.15+$hScale), 2);
	        } elseif ($status == 3) {
	            // T池总数 没有上级，没有H池，没有K池，没有S池，加起来
	            $tCount = $userLevel['t_pond'] + $userLevel['h_pond'] + $userLevel['k_pond'] + $userLevel['s_pond'];
	            $dataRetail['n_pond'] = $userLevel['n_pond'] + round($tCount * (0.15+$hScale), 2);
	        }
	        

	        // 会员和VIP
	        if ($memberpay['level_id'] == 3 || $memberpay['level_id'] == 4) {

	            // H和K池比例
	            $hScale = round(0.1 / 4, 2);

	            $dataRetail['p_pond'] = $userLevel['p_pond'] + round($tCount * (0.05+$hScale), 2);
	            $dataRetail['z_pond'] = $userLevel['z_pond'] + round($tCount * (0.2+$hScale), 2);
	            $dataRetail['g_pond'] = $userLevel['g_pond'] + round($tCount * (0.2+$hScale), 2);

	        // 合伙人
	        } elseif ($memberpay['level_id'] == 5) {

	            // H和K池比例
	            $hScale = round(0.1 / 3, 2);

	            $dataRetail['z_pond'] = $userLevel['z_pond'] + round($tCount * (0.2+$hScale), 2);
	            $dataRetail['g_pond'] = $userLevel['g_pond'] + round($tCount * (0.2+$hScale), 2);

	        } else {
	            Log::write('超过等级返佣不了');
	        }
	        // 插入数据
	        $saveRetail = model('MemberRetail')->insert($dataRetail);

	    }
	    return $saveRetail;

	}//---end



}
