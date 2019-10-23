<?php
namespace app\api\controller;
use app\common\controller\Apibase;
use think\facade\Request;
use think\facade\Cache;

class Index extends Apibase
{	

    // 清除缓存
    public function index() {

        if (Request::isPost()) {
            
            $type = Request::param('type');

            if ($type == 'homeShow') {
                Cache::set('homeIndex', null);
                Cache::set('homeAdver', null);
                Cache::set('homeMore', null);
                return json(['status'=>1, 'msg'=>'清除数据成功']);
            } elseif ($type == 'artDetailShow') {
                $art = model('voice')->field('voice_id')->select();
                foreach ($art as $key => $val) {
                    Cache::set($val['voice_id'].'artContent', null);
                    Cache::set($val['voice_id'].'artRefresh', null);
                    Cache::set($val['voice_id'].'artComment', null);
                    Cache::set($val['voice_id'].'artClick', null);
                }
                return json(['status'=>1, 'msg'=>'清除数据成功']);
            } elseif ($type == 'proShow') {
                Cache::set('pick_price', null);
                Cache::set('level', null);
                Cache::set('proList', null);
            } elseif ($type == 'userShow') {
                Cache::set('userInfo', null);
                Cache::set('userLevel', null);
            }

        }

        return view('index');

    }


    /**
     * 首页显示
     */
    public function home() {

        if (Cache::get('homeIndex')) {
            $lists = Cache::get('homeIndex');
            $adver = Cache::get('homeAdver');
        } else {

            // 查找分类
            $lists = model('category')->field('id, name')->where(['pid'=>1])->order('sort desc')->select()->toArray();

            // 查找子分类
            foreach ($lists as $k => $v) {

                $voice = model('category')
                    ->field('v.voice_id, v.cid, v.type, v.title, v.cover, v.play_num, v.info')
                    ->alias('c')
                    ->rightJoin('voice v', 'v.cid = c.id')
                    ->where('v.cid=:id', ['id' => $v['id']])
                    ->order('v.update_time desc')
                    ->limit(4)
                    ->select()->toArray();
                foreach ($voice as $kk => $vv) {
                    $voice[$kk]['cover'] = URL_PATH.$vv['cover'];
                    $voice[$kk]['play_num'] = $vv['play_num'] > 999 ? '999+' : $vv['play_num'];
                }
                $lists[$k]['voice'] = $voice;
            }

            // 广告图
            // $adver = model('setting')->where(['name' => 'ADVER_PHOTO'])->value('values');
            // $adver = URL_PATH.$adver;
            $adver = '';

            Cache::set('homeIndex', $lists, 3600);
            Cache::set('homeAdver', $adver, 3600);

        }

        
    	return json(['status' => 1, 'lists' => $lists, 'adver' => $adver]);
    }



    /**
     * 查看更多
     */
    public function more ($cid) {

        if (Cache::get('homeMore')) {
            $lists = Cache::get('homeMore');
        } else {

            //查找分类
            $lists = model('category')->field('id, name')->where('id=:id', ['id'=>$cid])->select()->toArray();

            //查找子分类
            foreach ($lists as $k => $v) {
                
                $voice = model('category')
                    ->field('v.voice_id, v.cid, v.type, v.title, v.cover, v.play_num, v.info')
                    ->alias('c')
                    ->rightJoin('voice v', 'v.cid = c.id')
                    ->where('v.cid=:id', ['id' => $v['id']])
                    ->order('play_num desc')
                    ->select()->toArray();
                foreach ($voice as $kk => $vv) {
                    $voice[$kk]['cover'] = URL_PATH.$vv['cover'];
                    $voice[$kk]['play_num'] = $vv['play_num'] > 999 ? '999+' : $vv['play_num'];
                }
                $lists[$k]['voice'] = $voice;

            }
            Cache::set('homeMore', $lists, 3600);

        }

        
        
        return json(['status' => 1, 'lists' => $lists]);
    }

    
}
