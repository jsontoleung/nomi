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

        $all_search=array();
        $all_search2=array();
        $all_search3=array();

        $where[] = ['title', 'like', '%'.$content.'%'];
        $result = model('voice')->field('voice_id, title, cover')->where($where)->select()->toArray();
        if (!empty($result)) {

            foreach ($result as $k => $v) {
                if (substr($v['cover'], 0, 4) !== 'http') $result[$k]['cover'] = URL_PATH.$v['cover'];

                if (substr($v['cover'], 0, 4) !== 'http') $all_search[$k]['cover'] = URL_PATH.$v['cover'];
                $all_search[$k]['title'] = $v['title'];
                $all_search[$k]['voice_id'] = $v['voice_id'];
                $all_search[$k]['type'] = 1;

                
            }
            
        }
        // 商品
        $where2[] = ['name', 'like', '%'.$content.'%'];
        $result2 = model('product')->field('pro_id, name, photo')->where($where2)->select()->toArray();
        if (!empty($result2)) {

            foreach ($result2 as $k => $v) {
                if (substr($v['photo'], 0, 4) !== 'http') $all_search2[$k]['cover'] = URL_PATH.$v['photo'];
                $all_search2[$k]['title'] = $v['name'];
                $all_search2[$k]['pro_id'] = $v['pro_id'];
                $all_search2[$k]['type'] = 0;
            }
            
        }
        $all_search3=array_merge($all_search,$all_search2);
        
         return json(['status'=>1, 'result' => $all_search3]);


        return json(['status'=>0, 'msg' => '没有这样的文章', 'result' => array()]);
    } 


    
    
    
}