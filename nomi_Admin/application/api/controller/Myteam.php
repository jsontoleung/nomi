<?php
namespace app\api\controller;
use app\common\controller\Apibase;
use think\facade\Request;
use think\facade\Cache;


// 我的团队
class Myteam extends Apibase
{	

    // 优先加载
    public function  initialize() {
        parent::initialize();
    }


    /**
     * 我的团队
     */
    public function index () {

        $keys = Request::param('keys');

        // 芽粉
        $list = model('user')->field('user_id, headimg')->where('pid=:id', ['id' => $this->uid])->select();
        foreach ($list as $k => $v) {
            $listTwo = model('user')->field('user_id')->where('pid=:id', ['id' => $v['user_id']])->select();
        }

        $user['total'] = count($list) + count($listTwo);
        $user['headimg'] = model('user')->where('user_id=:id', ['id' => $this->uid])->value('headimg');
        
        if (empty($keys) || $keys == 0) {
            
            $junior = model('user')
                ->field('u.user_id, u.nickname, u.level, l.level_type, u.top_meney, u.integral')
                ->alias('u')
                ->leftJoin('user_level l', ['u.level = l.level_id'])
                ->where('pid=:id', ['id' => $this->uid])
                ->select();
            foreach ($junior as $k => $v) {
                $v['nickname'] = preg_replace('/\[\[.*?\]\]/', '', $v['nickname']);
            }

        } elseif ($keys == 1) {
            
            $juniorOne = model('user')->field('user_id')->where('pid=:id', ['id' => $this->uid])->select();
            if (empty($juniorOne)) {
                
                $junior = array();

            } else {

                foreach ($juniorOne as $k => $v) {
                    $junior = model('user')
                        ->field('u.user_id, u.nickname, u.level, l.level_type, u.top_meney, u.integral')
                        ->alias('u')
                        ->leftJoin('user_level l', ['u.level = l.level_id'])
                        ->where('pid=:id', ['id' => $v['user_id']])
                        ->select();
                    foreach ($junior as $k => $v) {
                        $v['nickname'] = preg_replace('/\[\[.*?\]\]/', '', $v['nickname']);
                        $v['level_type'] = $v['level'] == 0 ? '游客' : $v['level_type'];
                    }
                }

            }
            

        }
        

        $showDatas = array(
            'status' => 1,
            'user' => $user,
            'junior' => $junior,
        );
        return json($showDatas);

    }
    

}