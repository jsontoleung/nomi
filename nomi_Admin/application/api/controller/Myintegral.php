<?php
namespace app\api\controller;
use app\common\controller\Apibase;
use think\facade\Request;
use think\facade\Cache;
use think\Db;


// 我的积分
class Myintegral extends Apibase
{	

    // 优先加载
    public function  initialize() {
        parent::initialize();
    }


    /**
     * 我的积分首页
     */
    public function index() {

    	$user = model('user')->field('user_id, balance, top_meney, return_money')->where('user_id=:id', ['id' => $this->uid])->find();

        $total = array();
        // 奖励金余额
        $total['balance'] = number_format($user['balance'] + $user['return_money'], 2);
        // $total['balance'] = '1.00';

        // 累计收益
        $total['grand'] = number_format($user['top_meney'] + $user['return_money'] + $user['balance'], 2);
        // $total['grand'] = '提现功能于9月5号开放';

        
        // 今天时间
        $start = date('Y-m-d 00:00:00', time());
        $end = date('Y-m-d 23:59:59', time());
        $today['start'] = strtotime($start);
        $today['end'] = strtotime($end);

        // 今日收入   
        $one_uid = model('MemberOrder')
            ->field('one_level_money')
            ->where('one_uid=:id', ['id' => $this->uid])
            ->whereTime('add_time', 'between', [$today['start'], $today['end']])
            ->select();
        $todayOne = 0;
        if (!empty($one_uid)) {
            
            foreach ($one_uid as $k => $v) {
                
                $todayOne += $v['one_level_money'];

            }

        } else {
            $todayOne = 0;
        }

        $second_uid = model('MemberOrder')
            ->field('second_level_money')
            ->where('second_uid=:id', ['id' => $this->uid])
            ->whereTime('add_time', 'between', [$today['start'], $today['end']])
            ->select();
        $todaySecond = 0;
        if (!empty($second_uid)) {
            
            foreach ($second_uid as $k => $v) {
                
                $todaySecond += $v['second_level_money'];

            }

        } else {
            $todaySecond = 0;
        }

        $todayMoney = $todayOne + $todaySecond;


        // 收入明细
            
        // 下级
        $record = model('memberOrder')
            ->alias('mo')
            ->field('u.headimg, u.nickname, mo.pay_time, mo.payment_money')
            ->leftJoin('user u', ['mo.uid = u.user_id'])
            ->where(['one_uid' => $user['user_id']])
            ->whereOr(['second_uid' => $user['user_id']])
            ->select();
        foreach ($record as $k => $v) {
            
            if (substr($v['headimg'], 0, 4) !== 'http') $v['headimg'] = URL_PATH . $v['headimg'];
            $record[$k]['nickname'] = $v['nickname'] . '-来自购买会员';
            $record[$k]['pay_time'] = date('m月d日 H:i');
            $record[$k]['payment_money'] = '+' . $v['payment_money'];

        }

        return json(['status'=>1, 'total' => $total, 'record' => $record]);


    }
    

}