<?php
namespace app\api\controller;
use app\common\controller\Apibase;
use think\facade\Request;
use think\facade\Cache;
use think\Db;


// 加入我们
class Member extends Apibase
{	

    // 优先加载
    public function  initialize() {
        parent::initialize();
    }


    public function home() {

        $user = model('user')
            ->alias('u')
            ->field('u.user_id, u.headimg, u.nickname, u.level, l.level_type')
            ->leftJoin('user_level l', ['u.level = l.level_id'])
            ->where('u.user_id=:id', ['id' => $this->uid])
            ->find();
        $user['nickname'] = preg_replace('/\[\[.*?\]\]/', '', $user['nickname']);
        //下一等级
        $user['nextLevel'] = $user['level']+1;
        $nextUser = model('Userlevel')
            ->where('level_id=:id', ['id' => $user['nextLevel']])
            ->value('level_type');

        if ($user['level'] > 1) {

            $userPrivilege = model('user_privilege')
                ->field('content')
                ->where('level_id=:id', ['id' => $user['level']])
                ->select();

        } else {
            $userPrivilege = array();
        }

        if ($user['level'] <= 7) {
            
            $nextPrivilege = model('user_privilege')
                ->field('content')
                ->where('level_id=:id', ['id' => $user['level']+1])
                ->select();

        } else {
            $nextPrivilege = array();
        }

        $showDatas = array(
            'status'            => 1,
            'user'              => $user,
            'userPrivilege'     => $userPrivilege,
            'nextPrivilege'     => $nextPrivilege,
            'nextUser'     => $nextUser,

        );
        return json($showDatas);
        
    }


    /**
     * 加入会员页面
     */
    public function add($level) {
        
        // 会员人数
        switch ($level) {
            case '2':
                $total = '3026';
                break;
            case '3':
                $total = '1553';
                break;
            case '4':
                $total = '752';
                break;
            
            default:
                $total = '566';
                break;
        }

        $order = Request::param('order');
        if (empty($order)) {
            $member['total'] = $total;
            $member['money'] = model('Userlevel')->where('level_id=:id', ['id' => $level])->value('money');
            $member['nickname'] = model('user')->where('user_id=:id', ['id' => $this->uid])->value('nickname');    
            $member['order'] = model('Userlevel')->where('level_id=:id', ['id' => $level])->value('level_type');
        } else {
            $member['total'] = $total;
            $member['money'] = model('Userlevel')->where('level_id=:id', ['id' => $level])->value('money');
            $member['nickname'] = model('user')->where('user_id=:id', ['id' => $this->uid])->value('nickname');
            $member['order'] = model('Userlevel')->where('level_id=:id', ['id' => $level])->value('level_type');
        }
        

        // 特权
        $list = model('UserPrivilege')
            ->field('name, content')
            ->where('level_id=:id', ['id' => $level])
            ->select();

    	if (!empty($member['money'])) {
            $member['type'] = 1;
    		return json(['status'=>1, 'member'=>$member, 'list'=>$list]);
    	} else {
            $member['type'] = 0;
            return json(['status'=>1, 'member'=>$member, 'list'=>$list]);
        }

    }




    /**
     * 支付会员后的页面
     */
    public function memberRetrun() {

        // 用户信息
        $user = model('user')
            ->field('u.user_id, u.headimg, u.qrcode, u.nickname, u.accont, u.level, u.integral, l.level_type')
            ->alias('u')
            ->leftJoin('user_level l', 'u.level = l.level_id')
            ->where('user_id=:id', ['id' => $this->uid])
            ->find();
        $user['nickname'] = preg_replace('/\[\[.*?\]\]/', '', $user['nickname']);

        // 特权
        $userPrivilege = model('UserPrivilege')
            ->field('content')
            ->where('level_id=:id', ['id' => $user['level']])
            ->select();
        // 赠送的产品
        if ($user['level'] == 3 || $user['level'] == 4) {
            $pro = model('product')
                ->alias('p')
                ->field('p.pro_id, p.name, p.price_before, p.photo')
                ->where('p.level=:id', ['id' => $user['level']])
                ->where(['p.type' => 0])
                ->where(['p.is_down' => 1])
                ->order('p.pro_id desc')
                ->select();
            foreach ($pro as $key => $value) {
                $value['photo'] = URL_PATH.$value['photo'];
            }
        } else {
            $pro = array();
        }

        $showDatas = array(
            'status'            => 1,
            'user'              => $user,
            'userPrivilege'     => $userPrivilege,
            'pro'               => $pro,

        );
        return json($showDatas);

    }




    /**
     * 选择赠送商品生成订单
     */
    public function giveOrder() {

        $proid = Request::post('proid');

        $out_trade_no = 'give'.time().$proid;

        // 购买用户
        $user = model('User')->field('user_id, channel_id')->where('user_id=:id', ['id'=>$this->uid])->find();
        if (empty($user)) {
            return json(['status'=>0, 'msg' => '没有该用户数据']);
        }

        // 库存表
        $ware_pro = model('WarehouseProduct')->where('proid=:id', ['id' => $proid])->find();
        $ware['current_cnt'] = $ware_pro['current_cnt'] - 1;
        if (1 > $ware_pro['current_cnt']) {
            return json(['status'=>0, 'msg' => '库存不足']);
        }

        // 收货地址
        $region = model('UserAddr')
            ->field('name, phone, province, city, area, address')
            ->where('uid=:id', ['id' => $user['user_id']])
            ->order(['is_detault' => 1])
            ->find();
        if (empty($region)) {
            return json(['status'=>0, 'msg' => '请填写收货地址']);
        }

        $inputs['order_sn'] = $out_trade_no;
        $inputs['channel_id'] = $user['channel_id'];
        $inputs['uid'] = $user['user_id'];
        $inputs['proid'] = $proid;
        $inputs['w_id'] = $ware_pro['wp_id'];
        $inputs['product_cnt'] = 1;
        $inputs['shipping_user'] = $region['name'];
        $inputs['shipping_phone'] = $region['phone'];
        $inputs['province'] = $region['province'];
        $inputs['city'] = $region['city'];
        $inputs['area'] = $region['area'];
        $inputs['address'] = $region['address'];
        $inputs['payment_method'] = 6;
        $inputs['order_money'] = 0;
        $inputs['order_point'] = 0;
        $inputs['create_time'] = time();
        $inputs['pay_status'] = 1;
        $inputs['order_status'] = 1;

        Db::startTrans();
        try {

            // 添加订单主表信息
            $add_master = model('orderMaster')->data($inputs)->save();
            // 购买后减少库存
            $minusWare = model('WarehouseProduct')->save($ware, ['proid' => $proid]);
            if ($add_master && $minusWare) {
                Db::commit();
                // 这里返回数据
                return json(['status'=>1, 'msg' => '赠送成功']);
            }
            
        } catch (Exception $e) {
            Db::rollback();
            throw $e;
        }

    }
    

}