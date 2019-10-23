<?php
namespace app\api\controller;
use app\common\controller\Apibase;
use app\common\config\Upload;
use phpqrcode\QRcode;
use think\facade\Request;
use think\facade\Cache;
use think\facade\Env;
use think\File;
use Log;
class Wxqrcode extends Apibase {


    public function qrcodes(){
        
        $superDefault = model('User')->where('user_id=:id', ['id' => $this->uid])->find();
        if (empty($superDefault)) {

            return json(['status'=>0, 'data' => '未找到相关信息']);

        } elseif (empty($superDefault['qrcode'])) {
            
            $res = $this->getAccessToken(APPID,APPSECRET,'client_credential');
            if ($res == 'success') {
                $token = Cache::get('wx_token');
                $access_token = $token['access_token'];
            }else{
                return json(['status'=>0, 'data' => $res]);
            }

            if (empty($access_token)) {
                return json(['status'=>0, 'data' => 'access_token为空，无法获取二维码']);
            }

            $path = 'pages/login/login?shareid='.$this->uid;

            $width = 430;

            $res2 = $this->getWXACodeUnlimit($access_token,$path,$width);

            //将生成的二维码保存到本地
            // $file ="/Uploads/".substr($path,strripos($path,"?")+1).".jpg";

            $file = "uploads/qrcode/".$this->uid. '-'. time().".jpg";

            file_put_contents('./'.$file,$res2);

            if (file_exists($file)) {

                $datas['qrcode'] = URL_PATH. '/' . $file;
                if (model('User')->save($datas, ['user_id' => $this->uid])) {
                    
                    return json(['status'=>1, 'data' => $datas['qrcode']]);

                }

            }else{
                return json(['status'=>0]);
            }

        } else {
            return json(['status'=>1, 'data' => $superDefault['qrcode']]);
        }

        
    }


    // 发送access_token
    public function getAccessToken($appid,$secret,$grant_type){
        if (empty($appid)||empty($secret)||empty($grant_type)) {
            return '参数错误';
        }
             // https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=APPID&secret=APPSECRET
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type={$grant_type}&appid={$appid}&secret={$secret}";
        if (Cache::get('wx_token')) {
            $token = Cache::get('wx_token');
            return 'success';
        }
        $json = https_request($url);
        $data=json_decode($json,true);
        if (empty($data['access_token'])) {
            return $data;
        }
        Cache::set('wx_token',$data,3600);
        return 'success';
    }
    // 获取带参数的二维码
    // 获取小程序码，适用于需要的码数量极多的业务场景。通过该接口生成的小程序码，永久有效，数量暂无限制。
    public function getWXACodeUnlimit($access_token,$path='',$width=430){
        if (empty($access_token)||empty($path)) {
            return 'error';
        }
             // https://api.weixin.qq.com/cgi-bin/wxaapp/createwxaqrcode?access_token=ACCESS_TOKEN
        $url = "https://api.weixin.qq.com/cgi-bin/wxaapp/createwxaqrcode?access_token={$access_token}";
        $data = array();
        $data['path'] = $path;
        //最大32个可见字符，只支持数字，大小写英文以及部分特殊字符：!#$&'()*+,/:;=?@-._~，其它字符请自行编码为合法字符（因不支持%，中文无法使用 urlencode 处理，请使用其他编码方式）
        $data['width'] = $width;
        //二维码的宽度，默认为 430px
        $json = https_request($url,json_encode($data));
        return $json;
    }




    // 生成产品二维码
    public function proQrcode() {

        $proid = Request::param('pro_id');

        $res = $this->getAccessToken(APPID,APPSECRET,'client_credential');
        if ($res == 'success') {
            $token = Cache::get('wx_token');
            $access_token = $token['access_token'];
        }else{
            return json(['status'=>0, 'data' => $res]);
        }

        if (empty($access_token)) {
            return json(['status'=>0, 'data' => 'access_token为空，无法获取二维码']);
        }

        // $path = 'pages/product/detail?pro_id='.$proid;
        // $path = 'pages/product/detail/index?pro_id='.$proid;
        $path = 'pages/login/login?prouid='.$this->uid.'&pro_id='.$proid;

        $width = 430;

        $res2 = $this->getWXACodeUnlimit($access_token,$path,$width);
        // $data['qrcode']='data:image/png;base64,'.base64_encode($res2);
        // print_r($data);die;
        
        $file = "uploads/qrcode/product/".$this->uid. '-'. time().".jpg";

        file_put_contents('./'.$file,$res2);

        if (file_exists($file)) {
            
            //二维码
            // $data['qrcode'] = base64EncodeImage($file);
            $data['qrcode'] = URL_PATH.'/'.$file;

            // 头像
            $headimg = model('User')->field('headimg,nickname')->where('user_id=:id', ['id' => $this->uid])->find();
            
            $headimgs = curl_url($headimg['headimg'],1);

            $data['headimg'] = $headimgs['data'];

            $data['name'] = $headimg['nickname'];
            
            // $data['headimg'] = $headimg;

            //产品图片
            // $photo = model('Product')->where('pro_id=:id', ['id' => $proid])->value('photo_group');
            // $photos = explode(',', $photo);
            // $daddd=file_get_contents('.'.$photos[0]);
            // $data['photo']='data:image/png;base64,'.base64_encode($daddd);
            // 
            //产品图片
            $photo = model('Product')->field('photo_group,act_name')->where('pro_id=:id', ['id' => $proid])->find();
            $photos = explode(',', $photo['photo_group']);

            $setting_act = model('setting')->where('name', 'ACTNAME')->value('values');

            $act_name=$setting_act;
            if($photo['act_name']){
            	$act_name=$photo['act_name'];
            }
            $data['act']=$act_name;
            
            // $data['photo'] = imgtobase64(URL_PATH. $photos[0]);
            $data['photo'] = URL_PATH. $photos[0];

            return json(['status' => 1, 'data' => $data]);

        }




    }





}