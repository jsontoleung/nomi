<?php
namespace app\api\controller;
use app\common\controller\Apibase;
use think\facade\Request;
use think\facade\Cache;
use think\Db;

class Search extends Apibase
{	

    // 优先加载
    public function  initialize() {
        parent::initialize();
    }
    

    /**
     * 搜索功能
     */
    public function search($content) {

        $where[] = ['title', 'like', '%'.$content.'%'];
        $result = model('voice')->field('voice_id, title, cover')->where($where)->select()->toArray();
        if (!empty($result)) {

            foreach ($result as $k => $v) {
                if (substr($v['cover'], 0, 4) !== 'http') $result[$k]['cover'] = URL_PATH.$v['cover'];
            }
            return json(['status'=>1, 'result' => $result]);
            
        }

        return json(['status'=>0, 'msg' => '没有这样的文章', 'result' => array()]);
    } 


    
    
    
}