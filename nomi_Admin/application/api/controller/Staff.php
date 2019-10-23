<?php
namespace app\api\controller;
use app\common\controller\Apibase;
use think\facade\Request;
use think\facade\Cache;
use think\Db;
use think\QRcode;

class Staff extends Apibase
{	

    // 优先加载
    public function  initialize() {
        parent::initialize();
    }
    // 生成二维码
    public function qrcode(){
        $qrcode = new QRcode();
        // 生成二维码
        $qrcode_data['order_sn']=time();
        $qrcode_obj=json_encode($qrcode_data); 
        
        $errorCorrectionLevel = 'H';    //容错级别  
        $matrixPointSize = 6;           //生成图片大小 
        $path = 'uploads/appointment/appointment_'.$qrcode_data['order_sn'].'.jpg';
        $qrcode::png($qrcode_obj,$path , $errorCorrectionLevel, $matrixPointSize, 2);
        ob_start();//开启缓冲区
        QRcode::png($qrcode_obj, false, 'H', 6, 2);//生成二维码
        $img = ob_get_contents();//获取缓冲区内容
        ob_end_clean();//清除缓冲区内容
        // $imgInfo = 'data:png;base64,' . chunk_split(base64_encode($img));//转base64
        ob_flush();
        return json($path);
    }
    // 更改订单状态
    public function orderstat(){
        $order_data=$_POST;
        // 查询订单id
        $order_id=Db::name('order_master')->where(['order_sn'=>$order_data['order_sn']])->find();
        $order_in=Db::name('serve_goods')->where(['order_id'=>$order_id['order_id'],'is_sign_in'=>0])->find();
        $order_count=Db::name('serve_goods')->where(['order_id'=>$order_id['order_id'],'is_sign_in'=>1])->count();
        $count=$order_id['product_cnt']-$order_count;
        if(empty($order_in)){
            $result['msg']='请重新去预约，剩余服务次数'.$count;
            $result['code']=0;
            return json($result);
        }

        $order_s=Db::name('serve_goods')->where(['order_id'=>$order_id['order_id'],'is_sign_in'=>0])->update(['is_sign_in'=>1,'is_sign_in_time'=>time()]);
        if($order_s){
            $count=$count-1;
            $counted=$order_count+1;
            $result['msg']='签到成功,已服务次数'.$counted.'次,剩余次数'.$count.'次';
            // $result['msg']='订单号：'.$order_data['order_sn'].',签到成功。';
        }else{
            // $result['msg']='订单号：'.$order_data['order_sn'].',签到失败。';
            $result['msg']='签到失败,已服务次数'.$order_count.'次,剩余次数'.$count.'次';
        }
        $result['code']=0;
        return json($result);
    }
    
    /**
     * 是否登录
     */
    public function islogin() {
        if(session('staff_login')){
            $result['code']=0;
            $result['msg']='登录成功';
            return json($result);
        }
        $result['code']=1;
        $result['msg']='登录失败';
        return json($result);
    }

    /**
     * 登录
     */
    public function login() {
        
        if (Request::isPost()) {
            $staff_data=$_POST;
            if(empty($staff_data['name'])){
                $result['code']=1;
                $result['msg']='账号不能为空';
                return json($result);
            }
            if(empty($staff_data['pwd'])){
                $result['code']=1;
                $result['msg']='密码不能为空';
                return json($result);
            }
            // 是否存在员工账号
            $user = Db::name('staff')
                ->where('name',$staff_data['name'])
                ->where('pwd', md5($staff_data['pwd']))
                ->find();
            if(empty($user)){
                $result['code']=1;
                $result['msg']='该员工不存在';
                return json($result);
            }
            $result['code']=0;
            $result['msg']='登录成功';
            return json($result);
        }
    }
    /**
     * 分享
     */
    public function share() {
        $getSignPackage=$this->getSignPackage($_POST['url'],'wx130414cb2759d52a','74b578e2ab45c378118f68d8bd2278e5');
        return json($getSignPackage);
    }

    public function getSignPackage($url,$appId,$appSecret) {
        $jsapiTicket = $this->getJsApiTicket($appId,$appSecret);
        $timestamp = time();
        $nonceStr = $this->createNonceStr();

        // 这里参数的顺序要按照 key 值 ASCII 码升序排序
        $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";

        $signature = sha1($string);

        $signPackage = array(
          "appId"     => $appId,
          "nonceStr"  => $nonceStr,
          "timestamp" => $timestamp,
          "url"       => $url,
          "signature" => $signature,
          "rawString" => $string
        );
        return $signPackage; 
    }

  public function createNonceStr($length = 16) {
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    $str = "";
    for ($i = 0; $i < $length; $i++) {
      $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
    }
    return $str;
  }

  public function getJsApiTicket($appId,$appSecret) {
    // // jsapi_ticket 应该全局存储与更新，以下代码以写入到文件中做示例
    $data = json_decode(file_get_contents("./scan/jsapi_ticket.json"));
    if (empty($data)||(!empty($data)&&$data->expire_time < time())) {
      $accessToken = $this->getAccessToken($appId,$appSecret);
      // 如果是企业号用以下 URL 获取 ticket
      // $url = "https://qyapi.weixin.qq.com/cgi-bin/get_jsapi_ticket?access_token=$accessToken";
      $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=$accessToken";
      $res = json_decode($this->httpGet($url));
      $ticket = $res->ticket;
      if ($ticket) {
        $data->expire_time = time() + 7000;
        $data->jsapi_ticket = $ticket;
        $fp = fopen("./scan/jsapi_ticket.json", "w");
        fwrite($fp, json_encode($data));
        fclose($fp);
      }
    } else {
      $ticket = $data->jsapi_ticket;
    }
    // $accessToken = $this->getAccessToken($appId,$appSecret);
    //   // 如果是企业号用以下 URL 获取 ticket
    //   // $url = "https://qyapi.weixin.qq.com/cgi-bin/get_jsapi_ticket?access_token=$accessToken";
    //   $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=$accessToken";
    //   $res = json_decode($this->httpGet($url));
    //   $ticket = $res->ticket;

      return $ticket;
  }

  public function getAccessToken($appId,$appSecret) {
    //使用客户公众号用以下URL获取access_token
    $data = json_decode(file_get_contents("./scan/access_token.json"));
    if (empty($data)||(!empty($data)&&$data->expire_time < time())) {
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$appId&secret=$appSecret";
        $res = json_decode($this->httpGet($url));
        $access_token = $res->access_token;

        if ($access_token) {
            $data->expire_time = time() + 7000;
            $data->access_token = $access_token;
            $fp = fopen("./scan/access_token.json", "w");
            fwrite($fp, json_encode($data));
            fclose($fp);
        }
    } else {
        $access_token = $data->access_token;
    }
    // $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$appId&secret=$appSecret";
    // $res = json_decode($this->httpGet($url));
    // $access_token = $res->access_token;
    
    return $access_token;
  }

  public function httpGet($url) {
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_TIMEOUT, 500);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_URL, $url);

    $res = curl_exec($curl);
    curl_close($curl);

    return $res;
  }

}