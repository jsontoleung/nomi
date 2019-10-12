<?php
namespace app\api\controller;
use app\common\controller\Apibase;
use think\facade\Request;
use think\facade\Cache;
use think\Db;

class Voice extends Apibase
{

    // 优先加载
    public function  initialize() {
        parent::initialize();
    }


    /**
     * 首页显示
     */
    public function detail() {

        if (Request::isPost()) {
            // 详情id
            $id = Request::param('id');

            // voice_id
            $vid = model('voice')->where('voice_id=:id', ['id'=>$id])->value('voice_id');

            // 是否有点赞
            $like = model('like')->where('uid=:uid', ['uid' => $this->uid])->where('voiceid=:id', ['id' => $vid])->count();

            // 是否有收藏
            $collect = model('collect')->where('uid=:uid', ['uid' => $this->uid])->where('voiceid=:id', ['id' => $vid])->count();

            // 主播
            $anchor = model('anchor')
                ->field('an.anchor_id, an.anchor_name, an.anchor_school, an.anchor_job, an.anchor_headimg, an.anchor_info, an.care_num, v.voice_content, v.like_num, v.collect_num')
                ->alias('an')
                ->leftJoin('voice v', 'an.anchor_id = v.anchorid')
                ->where(['v.voice_id' => $vid])
                ->find();
            $anchor['anchor_headimg'] = URL_PATH.$anchor['anchor_headimg'];

            // 是否有关注
            $care = model('carefor')->where('uid=:uid', ['uid'=>$this->uid])->where('anchorid=:id', ['id'=>$anchor['anchor_id']])->count();

            // 集数
            $count = model('VoiceDetail')->where('voiceid=:id', ['id' => $id])->count();
            //第一视频
            // detail_id
            $detail_id = Request::param('detail_id');
            $did = model('VoiceDetail')->where('detail_id=:id', ['id'=>$detail_id])->value('detail_id');
            if ($did) {
                $detail = model('VoiceDetail')->where('detail_id=:id', ['id'=>$detail_id])->value('video');
            } else {
                $detail = model('VoiceDetail')->where('voiceid=:id', ['id' => $id])->order('sort asc')->value('video');
            }
            $detail = URL_PATH.$detail;
            
            // 循环视频
            $select = Request::param('select');
            // 计算视频的集数
            $listSet = voiceSet($count, 50);
            if ($select == 1) {

                $lists = model('VoiceDetail')
                    ->field('detail_id')
                    ->where('voiceid=:id', ['id' => $id])
                    ->limit(50)
                    ->select();
                foreach ($lists as $k => $v) {
                    $lists[$k]['num'] = $k+1;
                }


            } else {
                $lists = model('VoiceDetail')
                    ->field('vd.detail_id, vd.title, vd.info, vd.cover, vd.video, vd.size, vd.longtime, vd.is_charge, vd.point, vd.money, an.anchor_name')
                    ->alias('vd')
                    ->leftJoin('voice v', 'vd.voiceid = v.voice_id', 'left')
                    ->leftJoin('anchor an', 'v.anchorid = an.anchor_id', 'left')
                    ->where('vd.voiceid=:id', ['id' => $id])
                    ->select();
                foreach ($lists as $k => $v) {
                    $lists[$k]['num'] = $k+1;
                    $lists[$k]['cover'] = URL_PATH.$v['cover'];
                    $lists[$k]['video'] = URL_PATH.$v['video'];
                    $lists[$k]['size'] = sizecount($v['size']);
                    $lists[$k]['longtime'] = changeTimeType($v['longtime']);
                }
            }
            

            // 评论
            $comment = model('comment')->field('com.comment_id, com.content, com.like, com.create_time, u.nickname, u.avatar')
                ->alias('com')
                ->leftJoin('user u', 'u.user_id = com.uid', 'left')
                ->where('type', 'in', '2,3')
                ->where('com.pid=:pid', ['pid' => 0])
                ->where('com.type_id=:id', ['id' => $vid])
                ->order('com.like desc')
                ->select();

            foreach ($comment as $k => $v) {
                // 评论点赞
                $commentLike = model('like')->where(['uid'=>$this->uid])->where(['commentid'=>$v['comment_id']])->count();
                $comment[$k]['commentLike'] = $commentLike;
                // 评论数
                $commentCount = model('comment')->where('pid=:id', ['id' => $v['comment_id']])->count();
                $comment[$k]['commentCount'] = $commentCount;
                // 下级评论
                $junior = model('comment')->field('com.comment_id, com.content, u.nickname')
                    ->alias('com')
                    ->leftJoin('user u', 'u.user_id = com.uid', 'left')
                    ->where('type', 'in', '2,3')
                    ->where('com.pid=:pid', ['pid' => $v['comment_id']])
                    ->order('com.like')
                    ->limit(2)
                    ->select();
                $comment[$k]['junior'] = $junior;
                $comment[$k]['avatar'] = URL_PATH.$v['avatar'];
                $comment[$k]['create_time'] = date('m-d H:i', $v['create_time']);
            }

        }

        $showDatas = array(
            'status' => 1,
            'anchor' => $anchor,
            'detail' => $detail,
            'listSet' => $listSet,
            'lists' => $lists,
            'count' => $count,
            'care' => $care,
            'like' => $like,
            'xing' => $collect,
            'comment' => $comment,
            'select' => $select,
        );
    	return json($showDatas);
    }



    /**
     * 目录--点击视频播放
     */
    public function playVideo() {

        if (Request::isPost()) {

            $detail_id = Request::param('id');
            $detail = model('VoiceDetail')->where('detail_id=:id', ['id'=>$detail_id])->value('video');
            $detail = URL_PATH.$detail;

        }

        return json(['status'=>1, 'detail' => $detail]);

    }



    /**
     * 下级评论列表
     */
    public function junior() {

        if (Request::isPost()) {

            $comid = Request::param('comid');

            $junior = model('comment')->field('com.comment_id, com.content, com.like, com.create_time, u.nickname, u.avatar')
                ->alias('com')
                ->leftJoin('user u', 'u.user_id = com.uid', 'left')
                ->where('type', 'in', '2,3')
                ->where('com.pid=:pid', ['pid' => $comid])
                ->order('com.like desc')
                ->select();
            foreach ($junior as $k => $v) {
                $juniorLike = model('like')->where('uid=:uid', ['uid'=>$this->uid])->where('commentid=:id', ['id'=>$v['comment_id']])->count();
                $junior[$k]['juniorLike'] = $juniorLike;
                $junior[$k]['avatar'] = URL_PATH.$v['avatar'];
                $junior[$k]['create_time'] = tranTime($v['create_time']);
                $junior[$k]['like'] = $v['like'] >= 10000 ? $v['like']/10000 .'w' : $v['like'];
            }

            $juniorCount = model('comment')->where('pid=:pid', ['pid' => $comid])->count();

        }
        
        return json(['status'=>1, 'junior' => $junior, 'juniorCount' => $juniorCount]);
    }



    /**
     * 下级评论列表点赞
     */
    public function juniorLike() {

        if (Request::isPost()) {

            $commentid = Request::param('id');
            $like = Request::param('like');
            if ($like == 1) {
                
                Db::startTrans();
                try {

                    $likeNum = model('comment')->where('comment_id=:id', ['id'=>$commentid])->where('pid', 'neq', 0)->value('like');
                    $inputs['like'] = $likeNum+1;
                    $saveComment = model('comment')->save($inputs, ['comment_id' => $commentid]);

                    $data['uid'] = $this->uid;
                    $data['commentid'] = $commentid;
                    $data['create_time'] = time();
                    $addLike = model('like')->data($data)->save();

                    if ($saveComment && $addLike) {
                        Db::commit();
                        return json(['status' => 1]);
                    }
                    return json(['status'=>0, 'msg'=>'没有插入数据库']);

                } catch (Exception $e) {
                    Db::rollback();
                    return json(['status'=>0, 'msg'=>'没有插入数据库']);
                }
                
            } else {

                Db::startTrans();
                try {

                    $likeNum = model('comment')->where('comment_id=:id', ['id'=>$commentid])->where('pid', 'neq', 0)->value('like');
                    $inputs['like'] = $likeNum-1;
                    $saveComment = model('comment')->save($inputs, ['comment_id' => $commentid]);

                    $like_id = model('like')
                        ->where('commentid=:id', ['id'=>$commentid])
                        ->where('uid=:uid', ['uid'=>$this->uid])
                        ->value('like_id');
                    if ($like_id) {
                        $del = model('like')->where(['like_id'=>$like_id])->delete();
                    } else {
                        return json(['status'=>0, 'msg'=>'数据库没有记录']);
                    }

                    if ($saveComment && $del) {
                        Db::commit();
                        return json(['status'=>1]);
                    }

                    
                } catch (Exception $e) {
                    Db::rollback();
                    return json(['status'=>0, 'msg'=>'没有插入数据库']);
                }

            }

        }


    }



    /**
     * 关注
     */
    public function carefor() {

        if (Request::isPost()) {

            // 详情id
            $id = Request::param('id');
            $care = Request::param('care');
            // voice表的anchorid
            $vid = model('voice')->where('voice_id=:id', ['id'=>$id])->value('anchorid');
            // 主播id
            $anid = model('anchor')->where(['anchor_id' => $vid])->value('anchor_id');
            
            if ($care == 1) {
                
                Db::startTrans();
                try {

                    $care_num = model('anchor')->where('anchor_id=:id', ['id'=>$anid])->value('care_num');
                    $inputs['care_num'] = $care_num+1;
                    $saveAnchor = model('anchor')->save($inputs, ['anchor_id'=>$anid]);

                    $data['uid'] = $this->uid;
                    $data['anchorid'] = $anid;
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

                    $care_num = model('anchor')->where('anchor_id=:id', ['id'=>$anid])->value('care_num');
                    $inputs['care_num'] = $care_num-1;
                    $saveAnchor = model('anchor')->save($inputs, ['anchor_id'=>$anid]);

                    $care_id = model('carefor')->where(['anchorid'=>$anid])->where(['uid'=>$this->uid])->value('care_id');
                    if ($care_id) {
                        $del = model('carefor')->where(['care_id'=>$care_id])->delete();
                    } else {
                        return json(['status'=>0, 'msg'=>'数据库没有记录']);
                    }

                    if ($saveAnchor && $del) {
                        Db::commit();
                        return json(['status'=>1]);
                    }
                    
                } catch (Exception $e) {
                    Db::rollback();
                    return json(['status'=>0, 'msg'=>'没有插入数据库']);
                }

            }

        }

    }


    /**
     * 点赞
     */
    public function like() {

        if (Request::isPost()) {

            // 详情id
            $id = Request::param('id');
            // voice_id
            $vid = model('voice')->where('voice_id=:id', ['id'=>$id])->value('voice_id');
            $like = Request::param('like');
            if ($like == 1) {

                Db::startTrans();
                try {
                    
                    $voice = model('voice')->where('voice_id=:id', ['id' => $vid])->value('like_num');
                    $data['like_num'] = $voice+1;
                    $addVoice = model('voice')->save($data, ['voice_id'=>$vid]);

                    $inputs['voiceid'] = $vid;
                    $inputs['uid'] = $this->uid;
                    $inputs['create_time'] = time();
                    $addLike = model('like')->data($inputs)->save();
                    if ($addVoice && $addLike) {
                        Db::commit();
                        return json(['status' => 1, 'like' => 1]);
                    }

                } catch (Exception $e) {
                    Db::rollback();
                    return json(['status'=>0, 'msg'=>'没有插入数据库']);
                }
                

            } else {

                Db::startTrans();
                try {

                    $voice = model('voice')->where('voice_id=:id', ['id' => $vid])->value('like_num');
                    $data['like_num'] = $voice-1;
                    $saveVoice = model('voice')->save($data, ['voice_id'=>$vid]);

                    $sql = model('like')->where(['voiceid'=>$vid])->where(['uid'=>$this->uid])->find();
                    if ($sql) {
                        $del = model('like')->where(['like_id'=>$sql['like_id']])->delete();
                    } else {
                        return json(['status'=>0, 'msg'=>'数据库没有记录']);
                    }
                    if ($saveVoice && $del) {
                        Db::commit();
                        return json(['status'=>1]);
                    }
                    
                } catch (Exception $e) {
                     Db::rollback();
                     return json(['status'=>0, 'msg'=>'数据库没有记录']);
                }
                
            }

        }

    }


    /**
     * 收藏
     */
    public function collect() {

        if (Request::isPost()) {

            // 详情id
            $id = Request::param('id');
            // voice_id
            $vid = model('voice')->where('voice_id=:id', ['id'=>$id])->value('voice_id');
            $xing = Request::param('xing');
            if ($xing == 1) {
                
                Db::startTrans();
                try {

                    $voice = model('voice')->where('voice_id=:id', ['id' => $vid])->value('collect_num');
                    $data['collect_num'] = $voice+1;
                    $addVoice = model('voice')->save($data, ['voice_id'=>$vid]);

                    $inputs['voiceid'] = $vid;
                    $inputs['uid'] = $this->uid;
                    $inputs['create_time'] = time();
                    $addXing = model('collect')->data($inputs)->save();
                    if ($addVoice && $addXing) {
                        Db::commit();
                        return json(['status' => 1, 'xing' => 1]);
                    }
                    return json(['status'=>0, 'msg'=>'添加数据库失败']);
                    
                } catch (Exception $e) {
                     Db::rollback();
                     return json(['status'=>0, 'msg'=>'添加数据库失败']);
                }

            } else {

                Db::startTrans();
                try {

                    $voice = model('voice')->where('voice_id=:id', ['id' => $vid])->value('collect_num');
                    $data['collect_num'] = $voice-1;
                    $saveVoice = model('voice')->save($data, ['voice_id'=>$vid]);

                    $sql = model('collect')->where(['voiceid'=>$vid])->where(['uid'=>$this->uid])->find();
                    if ($sql) {
                        $del = model('collect')->where(['collect_id'=>$sql['collect_id']])->delete();
                    } else {
                        return json(['status'=>0, 'msg'=>'数据库没有记录']);
                    }
                    if ($saveVoice && $del) {
                        Db::commit();
                        return json(['status'=>1]);
                    }
                    
                } catch (Exception $e) {
                    Db::rollback();
                    return json(['status'=>0, 'msg'=>'添加数据库失败']);
                }

            }

        }

    }


    /**
     * 评论点赞
     */
    public function commentLike() {

        if (Request::isPost()) {

            // 评论id
            $id = Request::param('id');
            $commentLike = Request::param('commentLike');
            if ($commentLike == 1) {
                
                Db::startTrans();
                try {

                    $comment = model('comment')->where('comment_id=:id', ['id'=>$id])->value('like');
                    $inputs['like'] = $comment+1;
                    $addComment = model('comment')->save($inputs, ['comment_id'=>$id]);

                    $data['commentid'] = $id;
                    $data['uid'] = $this->uid;
                    $data['create_time'] = time();
                    $addLike = model('like')->data($data)->save();

                    if ($addComment && $addLike) {
                        Db::commit();
                        return json(['status' => 1, 'msg' => '点赞成功']);
                    }
                    return json(['status'=>0, 'msg'=>'添加数据库失败']);
                    
                } catch (Exception $e) {
                    Db::rollback();
                    return json(['status'=>0, 'msg'=>'数据库没有记录']);
                }

            } else {

                Db::startTrans();
                try {

                    $comment = model('comment')->where('comment_id=:id', ['id'=>$id])->value('like');
                    $inputs['like'] = $comment-1;
                    $saveComment = model('comment')->save($inputs, ['comment_id'=>$id]);

                    $sql = model('like')->where(['commentid'=>$id])->where(['uid'=>$this->uid])->find();
                    if ($sql) {
                        $del = model('like')->where(['like_id'=>$sql['like_id']])->delete();
                    } else {
                        return json(['status'=>0, 'msg'=>'数据库没有记录']);
                    }
                    if ($saveComment && $del) {
                        Db::commit();
                        return json(['status'=>1]);
                    }
                    
                } catch (Exception $e) {
                    Db::rollback();
                    return json(['status'=>0, 'msg'=>'添加数据库失败']);
                }

            }

        }

    }
    
}