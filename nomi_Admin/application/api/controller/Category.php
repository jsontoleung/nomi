<?php
namespace app\api\controller;
use app\common\controller\Apibase;
use think\facade\Request;
use think\facade\Cache;
use think\Db;

class Category extends Apibase
{	

    // 优先加载
    public function  initialize() {
        parent::initialize();
    }



    /**
     * 分类显示--home_2
     */
    public function index() {

        if (Request::isPost()) {
            
            
            // 一级分类
        	$category = model('category')
                ->field('id, name')
                ->where(['pid' => 0])
                ->select();

            if (empty($id) || !isset($id) || ($id == 0)) {

                // 今日推荐
                $lists = model('voice')
                    ->field('v.voice_id, v.type, v.voice_title, v.voice_cover, v.play_num, an.anchor_name, an.anchor_job, an.anchor_school, an.anchor_headimg')
                    ->alias('v')
                    ->leftJoin('anchor an', 'v.anchorid = an.anchor_id')
                    ->where(['v.cid' => 1])
                    ->order('v.play_num desc')
                    ->select();

            } else {

                // 今日推荐
                $lists = model('voice')
                    ->field('v.voice_id, v.type, v.voice_title, v.voice_cover, v.play_num, an.anchor_name, an.anchor_job, an.anchor_school, an.anchor_headimg')
                    ->alias('v')
                    ->leftJoin('anchor an', 'v.anchorid = an.anchor_id')
                    ->where(['v.cid' => $id])
                    ->order('v.play_num desc')
                    ->select();

            }
            foreach ($lists as $k => $v) {
                $lists[$k]['voice_cover'] = URL_PATH.$v['voice_cover'];
                $lists[$k]['anchor_headimg'] = URL_PATH.$v['anchor_headimg'];
                $lists[$k]['play_num'] = $v['play_num'] > '999' ? '999+' : $v['play_num'];
            }

            // 讲师推荐
            $refresh = Request::param('refresh');
            if ($refresh == 1) {
               
                $anchor = model('anchor')
                    ->field('an.anchor_id, an.anchor_name, an.anchor_job, an.anchor_school, an.anchor_headimg, an.care_num')
                    ->alias('an')
                    ->orderRand()
                    ->limit(3)
                    ->select();

            } else {

                $anchor = model('anchor')
                    ->field('an.anchor_id, an.anchor_name, an.anchor_job, an.anchor_school, an.anchor_headimg, an.care_num')
                    ->alias('an')
                    ->order('an.care_num desc')
                    ->limit(3)
                    ->select();

            }
            foreach ($anchor as $k => $v) {

                $voice = model('voice')
                    ->field('voice_id, type, voice_title, voice_cover')
                    ->where('anchorid=:id', ['id'=>$v['anchor_id']])
                    ->order('like_num desc')
                    ->limit(2)
                    ->select();
                foreach ($voice as $kk => $vv) {
                    $voice[$kk]['voice_cover'] = URL_PATH.$vv['voice_cover'];
                }
                $anchor[$k]['voice'] = $voice;
                $anchor[$k]['anchor_headimg'] = URL_PATH.$v['anchor_headimg'];

                // 是否有关注
                $care = model('carefor')->where('uid=:uid', ['uid'=>$this->uid])->where('anchorid=:id', ['id'=>$v['anchor_id']])->count();
                $anchor[$k]['is_care'] = $care;

            }


            // 精选课程
            $selectRefresh = Request::param('selectRefresh');
            if ($selectRefresh == 1) {

                $select = model('voice')
                    ->field('v.voice_id, v.type, v.voice_title, v.voice_cover, v.play_num, v.like_num, an.anchor_name, an.anchor_job, an.anchor_headimg')
                    ->alias('v')
                    ->leftJoin('anchor an', 'v.anchorid = an.anchor_id')
                    ->orderRand()
                    ->limit(10)
                    ->select();
                
            } else {

                $select = model('voice')
                    ->field('v.voice_id, v.type, v.voice_title, v.voice_cover, v.play_num, v.like_num, an.anchor_name, an.anchor_job, an.anchor_headimg')
                    ->alias('v')
                    ->leftJoin('anchor an', 'v.anchorid = an.anchor_id')
                    ->order('v.play_num desc')
                    ->limit(10)
                    ->select();

            }
            
            foreach ($select as $k => $v) {
                $select[$k]['voice_cover'] = URL_PATH.$v['voice_cover'];
                $select[$k]['anchor_headimg'] = URL_PATH.$v['anchor_headimg'];
                $select[$k]['play_num'] = $v['play_num'] > '999' ? '999+' : $v['play_num'];
                $select[$k]['like_num'] = $v['like_num'] > '999' ? '999+' : $v['like_num'];
            }

        }

        $showDatas = array(
            'status'        => 1,
            'category'      => $category,
            'lists'         => $lists,
            'anchor'        => $anchor,
            'select'        => $select,

        );
        return json($showDatas);
    } 


    /**
     * 分类显示--home
     */
    public function home() {

        if (Request::isPost()) {
            
            // 一级分类
            $category = array(
                '0' => '推荐',
                '1' => '生活',
                '2' => '时尚',
                '3' => '专栏'
            );

            // 推荐
            $keys = Request::param('keys');

            if (empty($keys) || ($keys == 0)) {
                
                $lists = model('voice')
                    ->field('v.voice_id, v.type, v.cover, v.title, v.play_num, v.create_time')
                    ->alias('v')
                    ->order('v.play_num')
                    ->select();

            } elseif ($keys == 1) {
                
                $lists = model('voice')
                    ->field('v.voice_id, v.type, v.cover, v.title, v.play_num, v.create_time')
                    ->alias('v')
                    ->where(['v.type' => 1])
                    ->order('v.play_num')
                    ->select();

            } elseif ($keys == 2) {
                
                $lists = model('voice')
                    ->field('v.voice_id, v.type, v.cover, v.title, v.play_num, v.create_time')
                    ->alias('v')
                    ->where(['v.type' => 2])
                    ->order('v.play_num')
                    ->select();

            } elseif ($keys == 3) {
                
                $lists = model('voice')
                    ->field('v.voice_id, v.type, v.cover, v.title, v.play_num, v.create_time')
                    ->alias('v')
                    ->where(['v.type' => 3])
                    ->order('v.play_num')
                    ->select();

            }
            foreach ($lists as $k => $v) {
                
                if (!empty($lists)) {
                    
                    $lists[$k]['skipType'] = $v['type'];
                    $lists[$k]['play_num'] = $v['play_num'] >= 10000 ? round(($v['play_num'] / 10000), 2) . '万' : $v['play_num'];
                    $lists[$k]['cover'] = URL_PATH . $v['cover'];
                    $lists[$k]['create_time'] = date('m-d', $v['create_time']);
                    if ($v['skipType'] == 1) {
                        $lists[$k]['type'] = '文章';
                    } elseif ($v['skipType'] == 2) {
                        $lists[$k]['type'] = '视频';
                    } elseif ($v['skipType'] == 3) {
                        $lists[$k]['type'] = '音频';
                    } else {
                        $lists[$k]['type'] = '未知';
                    }

                }

            }

            // p($lists);die;

        }// post

        $showDatas = array(
            'status'        => 1,
            'category'      => $category,
            'lists'         => $lists,

        );
        return json($showDatas);

    }



    /**
     * 讲师推荐--关注
     */
    public function carefor() {
        
        if (Request::isPost()) {

            $anchorid = Request::param('id');
            $care = Request::param('care');
            if ($care == 1) {
                
                Db::startTrans();
                try {

                    $care_num = model('anchor')->where('anchor_id=:id', ['id'=>$anchorid])->value('care_num');
                    $inputs['care_num'] = $care_num+1;
                    $saveAnchor = model('anchor')->save($inputs, ['anchor_id'=>$anchorid]);

                    $data['uid'] = $this->uid;
                    $data['anchorid'] = $anchorid;
                    $data['create_time'] = time();
                    $addCare = model('carefor')->data($data)->save();

                    if ($saveAnchor && $addCare) {
                        Db::commit();
                        return json(['status' => 1, 'care' => 1]);
                    }
                    
                } catch (Exception $e) {
                    Db::rollback();
                    return json(['status'=>0, 'msg'=>'没有插入数据库']);
                }

            } else {

                Db::startTrans();
                try {

                    $care_num = model('anchor')->where('anchor_id=:id', ['id'=>$anchorid])->value('care_num');
                    $inputs['care_num'] = $care_num-1;
                    $saveAnchor = model('anchor')->save($inputs, ['anchor_id'=>$anchorid]);

                    $care_id = model('carefor')->where(['anchorid'=>$anchorid])->where(['uid'=>$this->uid])->value('care_id');
                    if ($care_id) {
                        $del = model('carefor')->where(['care_id'=>$care_id])->delete();
                    } else {
                        return json(['status'=>0, 'msg'=>'数据库没有记录']);
                    }

                    if ($saveAnchor && $del) {
                        Db::commit();
                        return json(['status'=>1, 'care' => 0]);
                    }
                    
                } catch (Exception $e) {
                    Db::rollback();
                    return json(['status'=>0, 'msg'=>'没有插入数据库']);
                }

            }

        }

    }

    
    
    
}