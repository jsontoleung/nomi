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
                ->order('u.register_time desc')
                ->select();
            foreach ($junior as $k => $v) {
                $v['nickname'] = preg_replace('/\[\[.*?\]\]/', '', $v['nickname']);
                $v['nickname']=$this->cut_str($v['nickname'], 1, 0).'**'.$this->cut_str($v['nickname'], 1, -1);
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
                        ->order('u.register_time desc')
                        ->select();
                    foreach ($junior as $k => $v) {
                        $v['nickname'] = preg_replace('/\[\[.*?\]\]/', '', $v['nickname']);
                        $v['nickname']=$this->cut_str($v['nickname'], 1, 0).'**'.$this->cut_str($v['nickname'], 1, -1);
                        $v['level_type'] = $v['level'] == 0 ? '游客' : $v['level_type'];
                    }
                }

            }
            

        }
        $len=count($junior);
        foreach ($junior as $key => $value) {
            $junior[$key]['len']=$len--;
        }
        

        $showDatas = array(
            'status' => 1,
            'user' => $user,
            'junior' => $junior,
        );
        return json($showDatas);

    }
    
    public function cut_str($string, $sublen, $start = 0, $code = 'UTF-8')
    {
        if($code == 'UTF-8')
        {
            $pa = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|\xe0[\xa0-\xbf][\x80-\xbf]|[\xe1-\xef][\x80-\xbf][\x80-\xbf]|\xf0[\x90-\xbf][\x80-\xbf][\x80-\xbf]|[\xf1-\xf7][\x80-\xbf][\x80-\xbf][\x80-\xbf]/";
            preg_match_all($pa, $string, $t_string);
            if(count($t_string[0]) - $start > $sublen) return join('', array_slice($t_string[0], $start, $sublen));
            return join('', array_slice($t_string[0], $start, $sublen));
        }
        else
        {
            $start = $start*2;
            $sublen = $sublen*2;
            $strlen = strlen($string);
            $tmpstr = '';

            for($i=0; $i< $strlen; $i++)
            {
                if($i>=$start && $i< ($start+$sublen))
                {
                    if(ord(substr($string, $i, 1))>129)
                    {
                        $tmpstr.= substr($string, $i, 2);
                    }
                    else
                    {
                        $tmpstr.= substr($string, $i, 1);
                    }
                }
                if(ord(substr($string, $i, 1))>129) $i++;
            }
            //if(strlen($tmpstr)< $strlen ) $tmpstr.= "...";
            return $tmpstr;
        }
    }
}