<?php
namespace app\api\controller;
use app\common\controller\Apibase;
use think\facade\Request;
use think\facade\Cache;
use think\Db;

class Article extends Apibase
{	

    // 优先加载
    public function  initialize() {
        parent::initialize();
    }

 

    /**
     * 文章详情
     */
    public function detail() {

        if (Request::isPost()) {

            // 文章id
            $id = Request::param('id');
            // 推荐文章
            $refresh = Request::param('refresh');

            if (!Cache::get('artContent') || !Cache::get('artRefresh') || !Cache::get('artComment') || !Cache::get('artClick')) {

                //文章内容
                $art = model('voice')
                    ->field('v.voice_id, v.title, v.cover_detail, v.content, v.share_num, v.info, v.proid')
                    ->alias('v')
                    ->where('v.voice_id=:id', ['id'=>$id])
                    ->find();

                if (substr($art['cover_detail'], 0, 4) !== 'http') $art['cover_detail'] = URL_PATH . $art['cover_detail'];

                // 点赞总数量
                $like_num = model('like')->where('voiceid=:id', ['id' => $id])->count();
                $art['like_num'] = $like_num;

                // 收藏总数量
                $collect_num = model('collect')->where('voiceid=:id', ['id' => $id])->count();
                $art['collect_num'] = $collect_num;

                // 是否有点赞
                $art['is_like'] = model('like')->where('uid=:uid', ['uid' => $this->uid])->where('voiceid=:id', ['id' => $id])->count();

                // 是否有收藏
                $art['is_collect'] = model('collect')->where('uid=:uid', ['uid' => $this->uid])->where('voiceid=:id', ['id' => $id])->count();
                
                //推荐文章
                if ($refresh == 1) {

                    $lists = model('voice')
                        ->field('v.voice_id, v.title, v.cover_detail, v.content, v.play_num, v.info')
                        ->alias('v')
                        ->orderRand()
                        ->limit(3)
                        ->select();

                } else {

                    $lists = model('voice')
                        ->field('v.voice_id, v.title, v.cover_detail, v.content, v.play_num, v.info')
                        ->alias('v')
                        ->order('v.play_num desc')
                        ->limit(3)
                        ->select();

                }
                foreach ($lists as $k => $v) {
                    $like_num = model('like')->where('voiceid=:id', ['id'=>$v['voice_id']])->count();
                    $lists[$k]['like_num'] = $like_num >= 10000 ? $like_num/10000 . 'w' : $like_num;
                    if (substr($lists[$k]['cover_detail'], 0, 4) !== 'http') $v['cover_detail'] = URL_PATH . $v['cover_detail'];
                    $lists[$k]['play_num'] = $v['play_num'] >=10000 ? $v['play_num']/10000 .'w' : $v['play_num'];
                }

                // 评论
                $comment = model('comment')->field('com.comment_id, com.content, com.create_time, u.nickname, u.headimg')
                    ->alias('com')
                    ->leftJoin('user u', 'u.user_id = com.uid', 'left')
                    ->where(['type'=>1])
                    ->where('com.pid=:pid', ['pid' => 0])
                    ->where('com.type_id=:id', ['id' => $id])
                    ->order('com.create_time desc')
                    ->limit(3)
                    ->select();

                foreach ($comment as $k => $v) {

                    $comment[$k]['nickname'] = emoji_decode($v['nickname']);

                    // 评论点赞
                    $commentLike = model('like')->where(['uid'=>$this->uid])->where(['commentid'=>$v['comment_id']])->count();
                    $comment[$k]['commentLike'] = $commentLike;

                    // 是否有评论点赞
                    $is_comlike = model('like')->where('uid=:uid', ['uid' => $this->uid])->where('commentid=:id', ['id' => $v['comment_id']])->count();
                    $comment[$k]['is_comlike'] = $is_comlike;

                    // 评论数
                    $commentCount = model('comment')->where('pid=:id', ['id' => $v['comment_id']])->count();
                    $comment[$k]['commentCount'] = $commentCount;

                    // 下级评论
                    $junior = model('comment')->field('com.comment_id, com.content, u.nickname')
                        ->alias('com')
                        ->leftJoin('user u', 'u.user_id = com.uid', 'left')
                        ->where(['type'=>1])
                        ->where('com.pid=:pid', ['pid' => $v['comment_id']])
                        ->order('com.create_time')
                        ->limit(2)
                        ->select();

                    foreach ($junior as $kk => $vv) {
                        $junior[$kk]['nickname'] = emoji_decode($v['nickname']);
                    }
                    $comment[$k]['junior'] = $junior;
                    $comment[$k]['create_time'] = date('m-d H:i', $v['create_time']);
                    if (substr($v['headimg'], 0, 4) !== 'http') {
                        $comment[$k]['headimg'] = URL_PATH . $v['headimg'];
                    }

                }

                // 产品链接按钮
                $product = model('product')->field('combo')->where(['pro_id' => $art['proid']])->find();
                $click = array();
                if (!empty($product)) {
                    
                    if ($product['combo'] > 0) {
                        
                        $click = model('product')->field('pro_id, name, photo')->where(['combo' => $product['combo']])->select();
                        foreach ($click as $k => $v) {
                            $click[$k]['photo'] = URL_PATH . $v['photo'];
                        }
                        

                    } else {
                        
                        $click = model('product')->field('pro_id, name, photo')->where(['pro_id' => $art['proid']])->select();
                        foreach ($click as $k => $v) {
                            $click[$k]['photo'] = URL_PATH . $v['photo'];
                        }
                        
                    }

                }

                Cache::set($id.'artContent', $art, 3600);
                Cache::set('artRefresh', $lists, 3600);
                Cache::set($id.'artComment', $comment, 3600);
                Cache::set($id.'artClick', $click, 3600);

            } else {

                $art = Cache::get($$id.'artContent');
                $lists = Cache::get('artRefresh');
                $comment = Cache::get($id.'artComment');
                $click = Cache::get($id.'artClick');

            }

        }
        
        $showDatas = array(
            'status'    => 1,
            'art'       => $art,
            'lists'     => $lists,
            'comment'   => $comment,
            'click'     => $click,
        );
        return json($showDatas);
    }




    /**
     * 点赞
     */
    public function like() {

        if (Request::isPost()) {

            // 详情id
            $voiceid = Request::param('voiceid');
            
            $is_like = Request::param('is_like');

            if ($is_like == 0) {

                $data['voiceid'] = $voiceid;
                $data['uid'] = $this->uid;
                $data['create_time'] = time();
                if (model('like')->data($data)->save()) {
                    Cache::set('artContent', null);
                    return json(['status' => 1, 'is_like' => 1]);
                }
                return json(['status'=>0, 'msg'=>'数据崩溃了']);

            } else {


                $like_id = model('like')
                    ->where(['voiceid'=>$voiceid])
                    ->where(['uid'=>$this->uid])
                    ->value('like_id');
                if ($like_id) {
                    
                    if (model('like')->where(['like_id'=>$like_id])->delete()) {
                        Cache::set($voiceid.'artContent', null);
                        return json(['status'=>1, 'is_like' => 0]);    
                    }
                    return json(['status'=>0, 'msg'=>'取消失败']);

                } else {
                    return json(['status'=>0, 'msg'=>'没有您的数据']);
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
            
            $collected = Request::param('collected');

            if ($collected == 0) {

                $data['voiceid'] = $id;
                $data['uid'] = $this->uid;
                $data['create_time'] = time();
                if (model('collect')->data($data)->save()) {
                    Cache::set($id.'artContent', null);
                    return json(['status' => 1, 'collected' => 1]);
                }
                return json(['status'=>0, 'msg'=>'数据崩溃了']);

            } else {

                $collect_id = model('collect')
                    ->where(['voiceid'=>$id])
                    ->where(['uid'=>$this->uid])
                    ->value('collect_id');
                if ($collect_id) {
                    
                    if (model('collect')->where(['collect_id'=>$collect_id])->delete()) {
                        Cache::set($id.'artContent', null);
                        return json(['status'=>1, 'collected' => 0]);
                    }
                    return json(['status'=>0, 'msg'=>'取消失败']);

                } else {
                    return json(['status'=>0, 'msg'=>'没有您的数据']);
                }
                    
            }

        }

    }



    /**
     * 评论点赞
     */
    public function commentLike() {

        if (Request::isPost()) {

            //文章id
            $id = Request::param('id');
            // 评论id
            $comid = Request::param('comid');
            $is_comlike = Request::param('is_comlike');

            if ($is_comlike == 0) {

                $data['commentid'] = $comid;
                $data['uid'] = $this->uid;
                $data['create_time'] = time();
                $addLike = model('like')->data($data)->save();
                // 评论
                $comment = model('comment')->field('com.comment_id, com.content, com.create_time, u.nickname, u.headimg')
                    ->alias('com')
                    ->leftJoin('user u', 'u.user_id = com.uid', 'left')
                    ->where(['type'=>1])
                    ->where('com.pid=:pid', ['pid' => 0])
                    ->where('com.type_id=:id', ['id' => $id])
                    ->order('com.create_time desc')
                    ->limit(3)
                    ->select();

                foreach ($comment as $k => $v) {

                    $comment[$k]['nickname'] = emoji_decode($v['nickname']);

                    // 评论点赞
                    $commentLike = model('like')->where(['uid'=>$this->uid])->where(['commentid'=>$v['comment_id']])->count();
                    $comment[$k]['commentLike'] = $commentLike;

                    // 是否有评论点赞
                    $is_comlike = model('like')->where('uid=:uid', ['uid' => $this->uid])->where('commentid=:id', ['id' => $v['comment_id']])->count();
                    $comment[$k]['is_comlike'] = $is_comlike;

                    // 评论数
                    $commentCount = model('comment')->where('pid=:id', ['id' => $v['comment_id']])->count();
                    $comment[$k]['commentCount'] = $commentCount;

                    // 下级评论
                    $junior = model('comment')->field('com.comment_id, com.content, u.nickname')
                        ->alias('com')
                        ->leftJoin('user u', 'u.user_id = com.uid', 'left')
                        ->where(['type'=>1])
                        ->where('com.pid=:pid', ['pid' => $v['comment_id']])
                        ->order('com.create_time')
                        ->limit(2)
                        ->select();
                    foreach ($junior as $kk => $vv) {
                        $junior[$k]['nickname'] = emoji_decode($v['nickname']);
                    }
                    $comment[$k]['junior'] = $junior;
                    $comment[$k]['create_time'] = date('m-d H:i', $v['create_time']);
                    if (substr($v['headimg'], 0, 4) !== 'http') {
                        $comment[$k]['headimg'] = URL_PATH . $v['headimg'];
                    }

                }
                if ($addLike) {
                    Cache::set($id.'artComment', null);
                    return json(['status' => 1, 'comment' => $comment]);
                }
                return json(['status'=>0, 'msg'=>'添加数据库失败']);

            } else {

                $sql = model('like')->where(['commentid'=>$comid])->where(['uid'=>$this->uid])->find();
                if ($sql) {
                    $del = model('like')->where(['like_id'=>$sql['like_id']])->delete();
                    // 评论
                    $comment = model('comment')->field('com.comment_id, com.content, com.create_time, u.nickname, u.headimg')
                        ->alias('com')
                        ->leftJoin('user u', 'u.user_id = com.uid', 'left')
                        ->where(['type'=>1])
                        ->where('com.pid=:pid', ['pid' => 0])
                        ->where('com.type_id=:id', ['id' => $id])
                        ->order('com.create_time desc')
                        ->limit(3)
                        ->select();

                    foreach ($comment as $k => $v) {

                        $comment[$k]['nickname'] = emoji_decode($v['nickname']);

                        // 评论点赞
                        $commentLike = model('like')->where(['uid'=>$this->uid])->where(['commentid'=>$v['comment_id']])->count();
                        $comment[$k]['commentLike'] = $commentLike;

                        // 是否有评论点赞
                        $is_comlike = model('like')->where('uid=:uid', ['uid' => $this->uid])->where('commentid=:id', ['id' => $v['comment_id']])->count();
                        $comment[$k]['is_comlike'] = $is_comlike;

                        // 评论数
                        $commentCount = model('comment')->where('pid=:id', ['id' => $v['comment_id']])->count();
                        $comment[$k]['commentCount'] = $commentCount;

                        // 下级评论
                        $junior = model('comment')->field('com.comment_id, com.content, u.nickname')
                            ->alias('com')
                            ->leftJoin('user u', 'u.user_id = com.uid', 'left')
                            ->where(['type'=>1])
                            ->where('com.pid=:pid', ['pid' => $v['comment_id']])
                            ->order('com.create_time')
                            ->limit(2)
                            ->select();
                        foreach ($junior as $kk => $vv) {
                            $junior[$k]['nickname'] = emoji_decode($v['nickname']);
                        }
                        $comment[$k]['junior'] = $junior;
                        $comment[$k]['create_time'] = date('m-d H:i', $v['create_time']);
                        if (substr($v['headimg'], 0, 4) !== 'http') {
                            $comment[$k]['headimg'] = URL_PATH . $v['headimg'];
                        }

                    }
                    if ($del) {
                        Cache::set($id.'artComment', null);
                        return json(['status'=>1, 'comment' => $comment]);
                    }
                } else {
                    return json(['status'=>0, 'msg'=>'数据库没有记录']);
                }

            }

        }

    }



    /**
     * 下级评论列表
     */
    public function junior() {

        if (Request::isPost()) {

            $comid = Request::param('comid');

            $junior = model('comment')->field('com.comment_id, com.content, com.create_time, u.nickname, u.headimg')
                ->alias('com')
                ->leftJoin('user u', 'u.user_id = com.uid')
                ->where(['type'=>1])
                ->where('com.pid=:pid', ['pid' => $comid])
                ->order('com.create_time desc')
                ->select();
            foreach ($junior as $k => $v) {

                $junior[$k]['nickname'] = emoji_decode($v['nickname']);

                $juniorLike = model('like')->where('commentid=:id', ['id'=>$v['comment_id']])->count();

                $junior[$k]['juniorLike'] = $juniorLike >= 10000 ? $juniorLike/10000 .'w' : $juniorLike;

                if (substr($v['headimg'], 0, 4) !== 'http') {
                    $junior[$k]['headimg'] = URL_PATH.$v['headimg'];
                }

                $junior[$k]['create_time'] = tranTime($v['create_time']);

                // 是否有评论点赞
                $is_comlike = model('like')
                    ->where('uid=:uid', ['uid' => $this->uid])
                    ->where('commentid=:cid', ['cid' => $v['comment_id']])
                    ->count();
                $junior[$k]['is_comlike'] = $is_comlike;

            }

            $juniorCount = model('comment')->where('pid=:comid', ['comid' => $comid])->count();

        }
        
        return json(['status'=>1, 'junior' => $junior, 'juniorCount' => $juniorCount]);
    }




    /**
     * 下级评论列表点赞
     */
    public function juniorLike() {

        if (Request::isPost()) {

            $artid = Request::param('artid');
            $z = Request::param('id');
            $like = Request::param('like');
            if ($like == 0) {

                $data['uid'] = $this->uid;
                $data['commentid'] = $commentid;
                $data['create_time'] = time();
                $addLike = model('like')->data($data)->save();

                $junior = model('comment')->field('com.comment_id, com.content, com.create_time, u.nickname, u.headimg')
                    ->alias('com')
                    ->leftJoin('user u', 'u.user_id = com.uid')
                    ->where(['type'=>1])
                    ->where('com.pid=:pid', ['pid' => $artid])
                    ->order('com.create_time desc')
                    ->select();
                foreach ($junior as $k => $v) {

                    $junior[$k]['nickname'] = emoji_decode($v['nickname']);

                    $juniorLike = model('like')->where('commentid=:id', ['id'=>$v['comment_id']])->count();

                    $junior[$k]['juniorLike'] = $juniorLike >= 10000 ? $juniorLike/10000 .'w' : $juniorLike;

                    if (substr($v['headimg'], 0, 4) !== 'http') {
                        $junior[$k]['headimg'] = URL_PATH.$v['headimg'];
                    }

                    $junior[$k]['create_time'] = tranTime($v['create_time']);

                    // 是否有评论点赞
                    $is_comlike = model('like')
                        ->where('uid=:uid', ['uid' => $this->uid])
                        ->where('commentid=:cid', ['cid' => $v['comment_id']])
                        ->count();
                    $junior[$k]['is_comlike'] = $is_comlike;

                }

                if ($addLike) {
                    Cache::set($artid.'artComment', null);
                    return json(['status' => 1, 'junior' => $junior]);
                }
                return json(['status'=>0, 'msg'=>'没有插入数据库']);
                
            } else {

                $like_id = model('like')
                    ->where('commentid=:id', ['id'=>$commentid])
                    ->where('uid=:uid', ['uid'=>$this->uid])
                    ->value('like_id');

                if ($like_id) {

                    $del = model('like')->where(['like_id'=>$like_id])->delete();

                    $junior = model('comment')->field('com.comment_id, com.content, com.create_time, u.nickname, u.headimg')
                        ->alias('com')
                        ->leftJoin('user u', 'u.user_id = com.uid')
                        ->where(['type'=>1])
                        ->where('com.pid=:pid', ['pid' => $artid])
                        ->order('com.create_time desc')
                        ->select();

                    foreach ($junior as $k => $v) {

                        $junior[$k]['nickname'] = emoji_decode($v['nickname']);

                        $juniorLike = model('like')->where('commentid=:id', ['id'=>$v['comment_id']])->count();

                        $junior[$k]['juniorLike'] = $juniorLike >= 10000 ? $juniorLike/10000 .'w' : $juniorLike;

                        if (substr($v['headimg'], 0, 4) !== 'http') {
                            $junior[$k]['headimg'] = URL_PATH.$v['headimg'];
                        }

                        $junior[$k]['create_time'] = tranTime($v['create_time']);

                        // 是否有评论点赞
                        $is_comlike = model('like')
                            ->where('uid=:uid', ['uid' => $this->uid])
                            ->where('commentid=:cid', ['cid' => $v['comment_id']])
                            ->count();
                        $junior[$k]['is_comlike'] = $is_comlike;

                    }

                    if ($del) {
                        Cache::set($artid.'artComment', null);
                        return json(['status' => 1, 'junior' => $junior]);
                    }

                } else {
                    return json(['status'=>0, 'msg'=>'数据库没有记录']);
                }

            }

        }


    }



    /**
     * 发表评论
     */
    public function publish() {

        $art_id = Request::param('art_id');
        $content = Request::param('content');

        $data['pid'] = 0;
        $data['type'] = 1;
        $data['type_id'] = $art_id;
        $data['uid'] = $this->uid;
        $data['content'] = $content;
        $data['create_time'] = time();

        if (model('comment')->data($data)->save()) {
            Cache::set($art_id.'artComment', null);
            return json(['status' => 1, 'msg' => '发表成功']);
        }
        return json(['status'=>0, 'msg'=>'发表失败']);

    }



    /**
     * 回复评论
     */
    public function reply() {

        $comid = Request::param('comid');
        $juniorid = Request::param('juniorid');
        $content = Request::param('content');

        if (empty($juniorid) || ($juniorid == 0)) {
            
            // 查找顶级评论的type_id
            $type_id = model('comment')->where('comment_id=:id', ['id'=>$comid])->value('type_id');

            $data['pid'] = $comid;
            $data['type'] = 1;
            $data['type_id'] = $type_id;
            $data['uid'] = $this->uid;
            $data['content'] = $content;
            $data['create_time'] = time();
            if (model('comment')->data($data)->save()) {

                $junior = model('comment')->field('com.comment_id, com.content, com.create_time, u.nickname, u.headimg')
                    ->alias('com')
                    ->leftJoin('user u', 'u.user_id = com.uid')
                    ->where(['type'=>1])
                    ->where('com.pid=:pid', ['pid' => $comid])
                    ->order('com.create_time desc')
                    ->select();

                foreach ($junior as $k => $v) {

                    $junior[$k]['nickname'] = emoji_decode($v['nickname']);

                    $juniorLike = model('like')->where('commentid=:id', ['id'=>$v['comment_id']])->count();

                    $junior[$k]['juniorLike'] = $juniorLike >= 10000 ? $juniorLike/10000 .'w' : $juniorLike;

                    if (substr($v['headimg'], 0, 4) !== 'http') {
                        $junior[$k]['headimg'] = URL_PATH.$v['headimg'];
                    }

                    $junior[$k]['create_time'] = tranTime($v['create_time']);

                    // 是否有评论点赞
                    $is_comlike = model('like')
                        ->where('uid=:uid', ['uid' => $this->uid])
                        ->where('commentid=:cid', ['cid' => $v['comment_id']])
                        ->count();
                    $junior[$k]['is_comlike'] = $is_comlike;

                }

                $juniorCount = model('comment')->where('pid=:comid', ['comid' => $comid])->count();
                Cache::set($comid.'artComment', null);

                return json(['status' => 1, 'junior' => $junior, 'juniorCount' => $juniorCount]);
            }
            return json(['status'=>0, 'msg'=>'发表失败']);

        } else {

            // 查好点击头像的评论信息
            $junior = model('comment')->where('comment_id=:id', ['id'=>$juniorid])->find();
            $data['pid'] = $junior['pid'];
            $data['type'] = 1;
            $data['type_id'] = $junior['type_id'];
            $data['by_uid'] = $juniorid;
            $data['uid'] = $this->uid;
            $data['content'] = $content;
            $data['create_time'] = time();
            if (model('comment')->data($data)->save()) {

                $junior = model('comment')->field('com.comment_id, com.content, com.create_time, u.nickname, u.headimg')
                    ->alias('com')
                    ->leftJoin('user u', 'u.user_id = com.uid')
                    ->where(['type'=>1])
                    ->where('com.pid=:pid', ['pid' => $comid])
                    ->order('com.create_time desc')
                    ->select();

                foreach ($junior as $k => $v) {

                    $junior[$k]['nickname'] = emoji_decode($v['nickname']);

                    $juniorLike = model('like')->where('commentid=:id', ['id'=>$v['comment_id']])->count();

                    $junior[$k]['juniorLike'] = $juniorLike >= 10000 ? $juniorLike/10000 .'w' : $juniorLike;

                    if (substr($v['headimg'], 0, 4) !== 'http') {
                        $junior[$k]['headimg'] = URL_PATH.$v['headimg'];
                    }

                    $junior[$k]['create_time'] = tranTime($v['create_time']);

                    // 是否有评论点赞
                    $is_comlike = model('like')
                        ->where('uid=:uid', ['uid' => $this->uid])
                        ->where('commentid=:cid', ['cid' => $v['comment_id']])
                        ->count();
                    $junior[$k]['is_comlike'] = $is_comlike;

                }
                
                $juniorCount = model('comment')->where('pid=:comid', ['comid' => $comid])->count();
                Cache::set($comid.'artComment', null);

                return json(['status' => 1, 'junior' => $junior, 'juniorCount' => $juniorCount]);
                
            }
            return json(['status'=>0, 'msg'=>'回复失败']);

        }

    }



    /**
     * 播放量
     */
    public function play_num ($artid) {

        if (Request::isPost()) {
            
            $article = model('voice')->field('play_num')->where('voice_id=:id', ['id' => $artid])->find();

            if ($article) {
                
                Db::startTrans();
                try {

                    $data['play_num'] = $article['play_num'] + 1;
                    $add = model('voice')->save($data, ['voice_id' => $artid]);
                    
                    $data2['type'] = 1;
                    $data2['voiceid'] = $artid;
                    $data2['uid'] = $this->uid;
                    $data2['addtime'] =time();
                    $add2 = model('VoicePlaylog')->data($data2)->save();
                    if ($add && $add2) {
                        Db::commit();
                        Cache::set($artid.'artContent', null);
                        return json(['status'=>1, 'msg'=>'欢迎观看']);
                    }
                    return json(['status'=>0, 'msg'=>'储存信息失败']);

                } catch (Exception $e) {
                    Db::rollback();
                    return (['status'=>0, 'msg'=>'等待事务提交']);
                }

            }

        }

    }




    /**
     * 文章分享
     */
    public function share ($id) {

        $user = model('user')->field('share_num, integral')->where('user_id=:id', ['id' => $this->uid])->find();
        $art = model('voice')->field('share_num')->where('voice_id=:id', ['id' => $id])->find();
        if ($art && $user) {
            
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
                
                // 文章分享
                $data['share_num'] = $art['share_num']+1;
                $add = model('voice')->save($data, ['voice_id' => $id]);

                // 分享日志
                $data2['type'] = 1;
                $data2['uid'] = $this->uid;
                $data2['type_id'] = $id;
                $data2['addtime'] =time();
                $add2 = model('share')->data($data2)->save();
                if ($addUser && $add && $add2) {
                    Db::commit();
                    Cache::set('artContent', null);
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