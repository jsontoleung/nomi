<?php
namespace app\api\controller;
use app\common\controller\Apibase;
use think\facade\Request;
use think\facade\Cache;
use think\Db;

class User extends Apibase
{	

    // 优先加载
    public function  initialize() {
        parent::initialize();
    }
    

    /**
     * 个人中心显示
     */
    public function index() {
        
        if (Request::isPost()) {

            // 用户信息
            $user = model('user')
                ->field('u.user_id, u.headimg, u.qrcode, u.nickname, u.accont, u.level, u.integral, l.level_type')
                ->alias('u')
                ->leftJoin('user_level l', 'u.level = l.level_id')
                ->where('user_id=:id', ['id' => $this->uid])
                ->find();
            $user['nickname'] = preg_replace('/\[\[.*?\]\]/', '', $user['nickname']);

            // 加入会员时弹出的框
            $level = model('Userlevel')->where('level_id', 'in', '2, 3, 4')->select();
            foreach ($level as $k => $v) {
                $level[$k]['pick_price'] = $v['pick_price'] * 10 .'折';
            }

        }
        
        $showDatas = array(
            'status'        => 1,
            'user'          => $user,
            'level'         => $level,

        );
        return json($showDatas);
    }



    /**
     * 个人分享
     */
    public function share () {

        $user = model('user')->field('share_num, integral')->where('user_id=:id', ['id' => $this->uid])->find();
        if ($user) {
            
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
                    
                    $data['integral'] = $user['integral'] + $shareLevel;

                }

                
                $data['share_num'] = $user['share_num']+1;
                $add = model('user')->save($data, ['user_id' => $this->uid]);

                $data2['type'] = 3;
                $data2['uid'] = $this->uid;
                $data2['addtime'] =time();
                $add2 = model('share')->data($data2)->save();
                if ($add && $add2) {
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




    /**
     * 最近学习、我的购买、我的收藏、我的关注
     */
    public function study ($keys) {

        // 栏目
        $category = array(
            '0' => '最近学习',
            '1' => '我的购买',
            '2' => '我的收藏',
            '3' => '我的关注'
        );
        
        $lists = array();
        if (empty($keys) || $keys == 0) {
            
            // 最近学习


        } elseif ($keys == 1) {
            
            // 我的购买
            $lists = model('OrderMaster')
            ->alias('om')
            ->field('pro.pro_id, pro.name, pro.photo, pro.price_after, om.product_cnt, om.payment_money, om.pay_time, om.order_sn,om.type')
            ->leftJoin('product pro', ['pro.pro_id = om.proid'])
            ->where(['om.order_status' => 1])
            ->where(['om.pay_status' => 1])
            ->where('om.uid=:id', ['id' => $this->uid])
            ->order('om.pay_time desc')
            ->select();
            foreach ($lists as $k => $v) {
                $lists[$k]['pay_time'] = date('m-d H:i', $v['pay_time']);
                if (substr($v['photo'], 0, 4) !== 'http') {
                    $lists[$k]['photo'] = URL_PATH . $v['photo'];
                }
            }
            

        } elseif ($keys == 2) {

            // 我的收藏
            $collect = model('collect')->where('uid=:id', ['id' => $this->uid])->select();

            if (!empty($collect)) {
                
                foreach ($collect as $k => $v) {
                    
                    if (!empty($v['voiceid']) || $v['voiceid'] != 0) {
                        
                        $lists[$k] = model('voice')
                            ->field('v.voice_id, v.title, v.cover, v.create_time, v.play_num')
                            ->alias('v')
                            ->leftJoin('collect co', 'v.voice_id = co.voiceid')
                            ->order('co.create_time desc')
                            ->find();

                        if (substr($lists[$k]['cover'], 0, 4) !== 'http') {
                             $lists[$k]['cover'] = URL_PATH .  $lists[$k]['cover'];
                        }
                         $lists[$k]['create_time'] = date('m-d H:i',  $lists[$k]['create_time']);
                         $lists[$k]['play_num'] =  $lists[$k]['play_num'] > 9999 ? '9999+' :  $lists[$k]['play_num'];
                         $lists[$k]['like_num'] = model('like')->where(['voiceid' =>  $lists[$k]['voice_id']])->count();
                         $lists[$k]['type'] = 1;

                    }else{

                        $lists[$k] = model('product')
                                ->field('v.pro_id, v.name, v.photo, v.create_time')
                                ->alias('v')
                                ->leftJoin('collect co', 'v.pro_id = co.proid')
                                ->order('co.create_time desc')
                                ->find();

                        if (substr($lists[$k]['photo'], 0, 4) !== 'http') {
                            $lists[$k]['cover'] = URL_PATH . $lists[$k]['photo'];
                        }
                        $lists[$k]['title'] = $lists[$k]['name'];
                        
                        $lists[$k]['create_time'] = date('m-d H:i', $lists[$k]['create_time']);
                        $lists[$k]['type'] = 0;
                    }


                }

            }

            // $collect2 = model('collect')->where('uid=:id', ['id' => $this->uid])->where('proid','>',0)->select();
            // if (!empty($collect2)) {
                
            //     foreach ($collect2 as $k => $v) {
                    
            //         $lists2[$k] = model('product')
            //                 ->field('v.pro_id, v.name, v.photo, v.create_time')
            //                 ->alias('v')
            //                 ->leftJoin('collect co', 'v.pro_id = co.proid')
            //                 ->where('v.pro_id=:id', ['id' => $v['proid']])
            //                 ->order('co.create_time desc')
            //                 ->find();
            //         if (substr($lists2[$k]['photo'], 0, 4) !== 'http') {
            //             $lists2[$k]['photo'] = URL_PATH . $lists2[$k]['photo'];
            //         }
            //         $lists2[$k]['create_time'] = date('m-d H:i', $lists2[$k]['create_time']);
            //         $lists2[$k]['type'] = 0;

            //     }

            // }

        } else {

            $lists = array();
            // $lists2 = array();

        }

        $showDatas = array(
            'status'        => 1,
            'category'      => $category,
            'lists'         => $lists,

        );
        return json($showDatas);

    }



    /**
     * 我的评论
     */
    public function comment () {

        if (Request::isPost()) {
                
            // 文章评论
            $list = model('comment')
                ->alias('com')
                ->field('com.pid, com.by_uid, com.content, com.create_time, v.voice_id, v.cover, v.title, u.headimg, u.nickname')
                ->leftJoin('user u', 'com.uid = u.user_id')
                ->leftJoin('voice v', 'com.type_id = v.voice_id')
                ->where('com.uid=:id', ['id' => $this->uid])
                ->where(['com.type' => 1])
                ->select();
            foreach ($list as $k => $v) {
                
                $list[$k]['cover'] = substr($v['cover'], 0, 4) !== 'http' ? URL_PATH.$v['cover'] : $v['cover'];
                $list[$k]['headimg'] = substr($v['headimg'], 0, 4) !== 'http' ? URL_PATH.$v['headimg'] : $v['headimg'];
                $list[$k]['create_time'] = date('m-d H:i', $v['create_time']);
                $list[$k]['by_uid'] = model('comment')->where('comment_id=:id',['id'=>$v['by_uid']])->value('uid');
                $list[$k]['rname_uid'] = model('user')->where('user_id=:id', ['id' => $v['by_uid']])->value('user_id');
                $list[$k]['rname'] = emoji_decode(model('user')->where('user_id=:id', ['id' => $v['by_uid']])->value('nickname'));
                $list[$k]['nickname'] = emoji_decode($v['nickname']);

            }

        }
        if (!isset($list)) {
            return json(['status'=>0, 'msg' => '没有数据']);
        }
        
        $showDatas = array(
            'status'        => 1,
            'list'      => $list,
        );
        return json($showDatas);

    }


    
    
    
}