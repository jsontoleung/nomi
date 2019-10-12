<?php
namespace app\api\controller;
use app\common\controller\Apibase;
use think\facade\Request;
use think\facade\Cache;
use think\Db;

class Product extends Apibase {

	private $product = null; // 数据表对象

	// 优先加载
	public function  initialize() {
		parent::initialize();
		$this->product = model('product');
	}


	public function home() {

		if (Request::isPost()) {

			$keys = Request::param('keys');

			// 一级分类
            $category = array(
                '0' => '优惠活动',
                '1' => '臻选优品',
                // '2' => '科技美容',
                '2' => '全部商品'
            );

	    	// 当前用户会员优惠价
			$pick_price = model('user')
				->alias('u')
				->field('l.level_id, l.pick_price, l.level_type')
				->leftJoin('user_level l', ['u.level = l.level_id'])
				->where('u.user_id=:id', ['id' => $this->uid])
				->find();

			// 下一等级会员优惠价
			$levelId = $pick_price['level_id']+1;
			$level = model('Userlevel')
				->field('level_id, level_type, pick_price, money')
				->where('level_id=:id', ['id' => $levelId])
				->find();
			
			if (empty($keys) || $keys == 0) {
				
				$list = model('product')
		        	->field('pro_id, type, photo, name, price_before, price_after, combo, end_time, volume ,buyNum')
		        	->where('combo', 'gt', 0)
		        	->where(['is_down' => 1])
		        	->order('sort desc, update_time desc')
		        	->select();

			} elseif ($keys == 1) {

		        $list = model('product')
		        	->field('pro_id, type, photo, name, price_before, price_after, combo, end_time, volume ,buyNum')
		        	->where('combo', 'eq', 0)
		        	->where(['is_down' => 1])
		        	->order('sort desc, volume desc')
		        	->select();

			} elseif ($keys == 2) {
				
				$list = model('product')
		        	->field('pro_id, type, photo, name, price_before, price_after, combo, end_time, volume ,buyNum')
		        	->where('combo', 'eq', 0)
		        	->where(['is_down' => 1])
		        	->order('sort desc, update_time desc')
		        	->select();

			}
	        foreach ($list as $k => $v) {

	        	if ($v['combo']>0) {
						
					$list[$k]['price_after'] = $v['price_after'];
					$list[$k]['end_time'] = '活动结束时间：' . date('m-d H:i', $v['end_time']);

				} else {

					$list[$k]['vip_price'] = sprintf("%.2f", ($v['price_after'] * $level['pick_price']));
					$list[$k]['vip_name'] = $level['level_type'].'价';

					$list[$k]['price_after'] = sprintf("%.2f", ($v['price_after'] * $pick_price['pick_price']));
					$list[$k]['after_name'] = $pick_price['level_type'].'价';

				}

	        	$list[$k]['photo'] = URL_PATH.$v['photo'];

	        }

	    }

		$showDatas = array(
			'status' => 1,
			'category' => $category,
			'list' => $list,
		);
		return json($showDatas);
	}



	/**
	 * 产品详情
	 */
	public function detail() {

		if (Request::isPost()) {

			$pro_id = Request::param('pro_id');

			// 是否收藏
			$is_collect = model('Collect')
				->where('uid=:uid', ['uid' => $this->uid])
				->where('proid=:id', ['id' => $pro_id])
				->count();
			
			// 轮播图
			$lunbo = $this->product->where('pro_id=:id', ['id'=>$pro_id])->value('photo_group');
			$lunbo = explode(',', $lunbo);
			foreach ($lunbo as $key => $value) {
				if (substr($value, 0, 4) !== 'http') {
					$lunbo[$key] = URL_PATH . $value;
				}
			}

			// 区域
			$region = model('UserAddr')
				->field('p.province, c.city, a.area')
				->alias('ua')
				->leftJoin('provinces p', 'ua.province = p.provinceid')
				->leftJoin('city c', 'ua.city = c.cityid')
				->leftJoin('areas a', 'ua.area = a.areaid')
				->where('uid=:id', ['id' => $this->uid])
				->order(['is_detault' => 1])
				->find();

			// 评论
			$comment = model('ProductComment')
				->field('pc.comment_id, pc.content, pc.update_time, u.headimg, u.nickname')
				->alias('pc')
				->leftJoin('user u', 'pc.uid = u.user_id')
				->where('pc.pro_id=:id', ['id'=>$pro_id])
				->order('update_time desc')
				->find();
			if (!empty($comment['update_time'])) {
				$comment['update_time'] = date('Y-m-d H:i:s', $comment['update_time']);
			}
			

			// 当前价钱
			$pick_price = model('user')
				->alias('u')
				->field('l.level_id, l.pick_price')
				->leftJoin('user_level l', ['u.level = l.level_id'])
				->where('u.user_id=:id', ['id' => $this->uid])
				->find();

			// 下一等级会员优惠价
			$levelId = $pick_price['level_id']+1;
			$level = model('Userlevel')
				->field('level_id, level_type, pick_price, money')
				->where('level_id=:id', ['id' => $levelId])
				->find();

			$pro = $this->product
				->alias('p')
				->field('p.pro_id, p.type, p.name, p.price_after, p.pledge_type, p.content, p.intro, p.combo, p.serve_num, wp.current_cnt')
				->leftJoin('WarehouseProduct wp', 'p.pro_id = wp.proid')
				->where('p.pro_id=:id', ['id'=>$pro_id])
				->find();
			
			if ($pro['combo'] > 0) {
						
				$pro['price_after'] = $pro['price_after'];

			} else {

				$pro['price_after'] = sprintf("%.2f", ($pro['price_after'] * $pick_price['pick_price']));
				$pro['level_id'] = $level['level_id'];
				$pro['level_type'] = $level['level_type']. '价';
				$pro['pick_price'] = $level['pick_price'];
				$pro['money'] = $level['money'];
				$pro['vip_price'] = sprintf("%.2f", ($pro['price_after'] * $level['pick_price']));

			}
			$pro['pledge_type'] = parse_config_attr($pro['pledge_type']);

			// 服务，选择店铺
			$shop = model('Shop')->select();
			foreach ($shop as $key => $value) {
				$pro['shop'] = model('Shop')
					->alias('p')
					->field('p.shop_id, p.shop_name, a.area')
					->leftJoin('areas a', ['p.area = a.areaid'])
					->where('p.pro_id' ,'like', "%$pro_id%")
					->select();
				
			}
			
			
			// 其他产品
			if ($pro['type'] == false) {
				$other = $this->product
					->field('pro_id, name, photo')
					->where('pro_id', 'not in', $pro_id)
					->limit(2)
					->select();
			} else {
				$other = $this->product
					->field('pro_id, name, photo')
					->where('pro_id', 'not in', $pro_id)
					->where('type', 'eq', 1)
					->limit(2)
					->select();
			}
			
			foreach ($other as $k => $v) {
				if (substr($v['photo'], 0, 4) !== 'http') $other[$k]['photo'] = URL_PATH . $v['photo'];
			}


		}


		$showDatas = array(
			'status' => 1,
			'lunbo' => $lunbo,
			'region' => $region,
			'comment' => $comment,
			'pro' => $pro,
			'other' => $other,
			'is_collect' => $is_collect,
		);
		return json($showDatas);
	}



	/**
	 * 收藏
	 */
	public function collect() {

		if (Request::param()) {
			
			$proid = Request::param('proid');
			$collected = Request::param('collected');
			if ($collected == 0) {
				
				$data['uid'] = $this->uid;
				$data['proid'] = $proid;
				$data['create_time'] = time();
				if(model('collect')->data($data)->save()) {

					return json(['status'=>1, 'msg' => '收藏成功', 'collected'=>1]);
				}
				return json(['status'=>1, 'msg' => '收藏失败']);

			} else {

				$collect_id = model('collect')
					->where('uid=:uid', ['uid'=>$this->uid])
					->where('proid=:id', ['id'=>$proid])
					->value('collect_id');
				if (!empty($collect_id) && isset($collect_id)) {

					if (model('collect')->where(['collect_id'=>$collect_id])->delete()) {
						
						return json(['status'=>1, 'msg' => '取消收藏', 'collected'=>0]);
					}
					return json(['status'=>1, 'msg' => '取消失败']);
				}
				return json(['status'=>1, 'msg' => '没有您的数据']);

			}

		}

	}



	/**
	 * 收货地址
	 */
	public function address() {

		if (Request::isPost()) {
			
			$addr = model('UserAddr')
				->field('addr.addr_id, addr.address, addr.name, addr.phone, p.province, c.city, a.area')
				->alias('addr')
				->leftJoin('provinces p', 'p.provinceid = addr.province')
				->leftJoin('city c', 'c.cityid = addr.city')
				->leftJoin('areas a', 'a.areaid = addr.area')
				->where('addr.uid=:id', ['id' => $this->uid])
				->where('addr.is_default=:id', ['id' => 1])
				->find();
			if (empty($addr)) {
				$lists[] = array();
			} else {
				$lists['addr_id'] = $addr['addr_id'];
				$lists['name'] = $addr['name'];
				$lists['phone'] = $addr['phone'];
				$lists['address'] = $addr['address'];
				$lists['areas'] = $addr['province'] .' - '. $addr['city'] .' - '. $addr['area'];
			}

		}
		
		$showDatas = array(
			'status' => 1,
			'lists' => $lists,
		);
		return json($showDatas);

	}



	/**
	 * 保存收货地址
	 */
	public function saveAddress() {

		if (Request::isPost()) {
			
			$inputs['addr_id'] = Request::param('addr_id');
			$inputs['name'] = Request::param('name');
			$inputs['phone'] = Request::param('phone');
			$inputs['areas'] = Request::param('areas');
			$inputs['address'] = Request::param('address');
			
			$areas = explode(',', $inputs['areas']);
			$data['province'] = model('provinces')->where(['province' => trim($areas[0])])->value('provinceid');
			$data['city'] = model('city')->where(['city' => trim($areas[1])])->value('cityid');
			$data['area'] = model('areas')->where(['area' => trim($areas[2])])->value('areaid');
			$data['uid'] = $this->uid;
			$data['address'] = $inputs['address'];
			$data['name'] = $inputs['name'];
			$data['phone'] = $inputs['phone'];
			$data['is_default'] = 1;
			$data['update_time'] = time();

			if (empty($inputs['addr_id'])) {
				
				if (model('UserAddr')->data($data)->save()) {

					return json(['status'=>1, 'msg' => '添加成功']);
				}
				return json(['status'=>0, 'msg' => '添加失败']);

			} else {

				if (model('UserAddr')->save($data, ['addr_id' => $inputs['addr_id']])) {

					return json(['status'=>1, 'msg' => '编辑成功']);
				}
				return json(['status'=>0, 'msg' => '编辑失败']);

			}

		}

	}



	/**
	 * 产品评论
	 */
	public function comment() {

		$pro_id = Request::param('pro_id');

		$evaluate['good'] = model('ProductComment')->where(['evaluate'=>1])->where('pro_id=:id',['id'=>$pro_id])->count();
		$evaluate['middle'] = model('ProductComment')->where(['evaluate'=>2])->where('pro_id=:id',['id'=>$pro_id])->count();
		$evaluate['bad'] = model('ProductComment')->where(['evaluate'=>3])->where('pro_id=:id',['id'=>$pro_id])->count();
		$top[0]['type'] = '全部';
		$top[1]['type'] = $evaluate['good'] > 999 ? '好评(999+)' : '好评('.$evaluate['good'].')';
		$top[2]['type'] = $evaluate['middle'] > 999 ? '中评(999+)' : '中评('.$evaluate['middle'].')';
		$top[3]['type'] = $evaluate['bad'] > 999 ? '差评(999+)' : '差评('.$evaluate['bad'].')';

		$types = Request::param('types');
		if (empty($types) || ($types == 0)) {
			$lists = model('ProductComment')
				->field('pc.comment_id, pc.content, pc.update_time, u.headimg, u.nickname')
				->alias('pc')
				->leftJoin('user u', 'pc.uid = u.user_id')
				->where('pc.pro_id=:id', ['id'=>$pro_id])
				->order('update_time desc')
				->select();
		} elseif ($types == 1) {
			
			$lists = model('ProductComment')
				->field('pc.comment_id, pc.content, pc.update_time, u.headimg, u.nickname')
				->alias('pc')
				->leftJoin('user u', 'pc.uid = u.user_id')
				->where('pc.pro_id=:id', ['id'=>$pro_id])
				->where(['evaluate' => 1])
				->order('update_time desc')
				->select();

		} elseif ($types == 2) {
			
			$lists = model('ProductComment')
				->field('pc.comment_id, pc.content, pc.update_time, u.headimg, u.nickname')
				->alias('pc')
				->leftJoin('user u', 'pc.uid = u.user_id')
				->where('pc.pro_id=:id', ['id'=>$pro_id])
				->where(['evaluate' => 2])
				->order('update_time desc')
				->select();

		} elseif ($types == 3) {
			
			$lists = model('ProductComment')
				->field('pc.comment_id, pc.content, pc.update_time, u.headimg, u.nickname')
				->alias('pc')
				->leftJoin('user u', 'pc.uid = u.user_id')
				->where('pc.pro_id=:id', ['id'=>$pro_id])
				->where(['evaluate' => 3])
				->order('update_time desc')
				->select();

		}
		
		foreach ($lists as $key => $value) {
			$lists[$key]['update_time'] = date('Y-m-d H:i:s', $value['update_time']);
			if (substr($value['headimg'], 0, 4) !== 'http') {
				$lists[$key]['headimg'] = URL_PATH . $value['headimg'];
			}
		}

		$showDatas = array(
			'status' => 1,
			'top' => $top,
			'lists' => $lists,
		);
		return json($showDatas);

	}




	/**
     * 商品分享
     */
    public function share ($id) {

        $user = model('user')->field('share_num, integral')->where('user_id=:id', ['id' => $this->uid])->find();
        $pro = $this->product->field('share_num')->where('pro_id=:id', ['id' => $id])->find();
        if ($pro) {
            
            Db::startTrans();
            try {

            	// 今天时间
                $start = date('Y-m-d 00:00:00', time());
                $end = date('Y-m-d 23:59:59', time());
                $today['start'] = strtotime($start);
                $today['end'] = strtotime($end);

                // 查找分享次数
                $shareTimes = model('share')
                    ->where('uid=:id', ['id' => $this->uid])
                    ->whereTime('addtime', 'between', [$today['start'], $today['end']])
                    ->count();

                // 分享赠送积分
                $shareLevel = model('setting')->where(['name' => 'SHARE_LEVEL'])->value('values');
                // 分享赠送限制次数
                $shareLimit = model('setting')->where(['name' => 'SHARE_LIMIT'])->value('values');

                if ($shareTimes <= $shareLimit) {
                    
                    $res['integral'] = $user['integral'] + $shareLevel;

                }

            	// 用户分享
            	$res['share_num'] = $user['share_num']+1;
                $addUser = model('user')->save($res, ['user_id' => $this->uid]);
                
                // 产品分享
                $data['share_num'] = $pro['share_num']+1;
                $add = $this->product->save($data, ['pro_id' => $id]);

                // 分享日志
                $data2['type'] = 2;
                $data2['uid'] = $this->uid;
                $data2['type_id'] = $id;
                $data2['addtime'] =time();
                $add2 = model('share')->data($data2)->save();
                if ($addUser && $add && $add2) {
                    Db::commit();
                    return json(['status'=>1, 'msg'=>'分享成功']);
                }
                return json(['status'=>0, 'msg'=>'储存信息失败']);

            } catch (Exception $e) {
                Db::rollback();
                return (['status'=>0, 'msg'=>'等待事务提交']);
            }

        }

    }







}