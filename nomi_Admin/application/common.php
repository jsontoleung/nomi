<?php

/*
** 应用公共函数
*/

function p($arr) {
	echo '<pre>'. print_r($arr, true). '</pre>';
}

/**
 * 发送HTTP请求方法
 * @param  string $url    请求URL
 * @param  array  $params 请求参数
 * @param  string $method 请求方法GET/POST
 * @return array  $data   响应数据
 */
function httpCurl($url, $params, $method = 'POST', $header = array(), $multi = false){
    date_default_timezone_set('PRC');
    $opts = array(
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_HTTPHEADER     => $header,
        CURLOPT_COOKIESESSION  => true,
        CURLOPT_FOLLOWLOCATION => 1,
        CURLOPT_COOKIE         =>session_name().'='.session_id(),
    );
    /* 根据请求类型设置特定参数 */
    switch(strtoupper($method)){
        case 'GET':
            // $opts[CURLOPT_URL] = $url . '?' . http_build_query($params);
            // 链接后拼接参数  &  非？
            $opts[CURLOPT_URL] = $url . '?' . http_build_query($params);
            break;
        case 'POST':
            //判断是否传输文件
            $params = $multi ? $params : http_build_query($params);
            $opts[CURLOPT_URL] = $url;
            $opts[CURLOPT_POST] = 1;
            $opts[CURLOPT_POSTFIELDS] = $params;
            break;
        default:
            throw new Exception('不支持的请求方式！');
    }
    /* 初始化并执行curl请求 */
    $ch = curl_init();
    curl_setopt_array($ch, $opts);
    $data  = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    if($error) throw new Exception('请求发生错误：' . $error);
    return  $data;
}

// https_request请求接口返回数据
function https_request($url, $data = null,$time_out=60,$out_level="s",$headers=array())
{
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_NOSIGNAL, 1);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
    if (!empty($data)){
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    }
    if($out_level=="s")
    {
        //超时以秒设置
        curl_setopt($curl, CURLOPT_TIMEOUT,$time_out);//设置超时时间
    }elseif ($out_level=="ms") 
    {
        curl_setopt($curl, CURLOPT_TIMEOUT_MS,$time_out);  //超时毫秒，curl 7.16.2中被加入。从PHP 5.2.3起可使用 
    }
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    if($headers)
    {
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);//如果有header头 就发送header头信息
    }
    $output = curl_exec($curl);
    curl_close($curl);
    return $output;
}



// ------------------------------------------逻辑--start----------------------------------------------------

/**
 * @author 计算视频的集数
 * @param $count 总条数 
 * @param $part 分割数
 * @param $custom 自定义二维数组名字
 */
function voiceSet ($count, $part, $custom='num') {

    for ($i=1; $i <= $count; $i+=$part) { 
        
        $lists[$i]["{$custom}"] = "$i-" . ($i+$part);

    }
    return $lists;

}





// ------------------------------------------逻辑--end----------------------------------------------------



// -----------------------------------------数组---start------------------------------------------------------



/**
 *  @author 组合输出分类树状列表图标(缩进)
 *  @param  $arr 数组
 *  @return arr
 */
function get_tree_icons($path) {
    $paths = explode('-', $path);
    $length = count($paths);
    $icons = '';
    // 顶级
    if($length == 2){
        $icons = '';
    }
    if($length > 2){
        $iconsNum = $length * ($length - 1);
        for($i = 0; $i < $iconsNum; $i++){
            $icons .= '&nbsp;&nbsp;';
        }
        $icons = $icons . '|---';
    }
    
    return $icons;
}


/**
 * @author  对象转为数组
 * @param   object 对象
 */
function object2array($object) {

    $result = array();
    foreach($object as $value){
        $result[] = json_decode($value, true);
    }
    return $result;
}

/**
 * @author   二维数组排序
 * @param    array   需要排序的数组
 * @param    key     需要根据某个key排序
 * @param    string  倒叙还是顺序
 */
function arraySort($array,$keys,$sort='asc') {
    $newArr = $valArr = array();
    foreach ($array as $key=>$value) {
        
        if (isset($valArr[$key])) {
            $valArr[$key] = $value[$keys]; 
        } else {
            $valArr[$key] = '';
        }
    }
    ($sort == 'asc') ?  asort($valArr) : arsort($valArr);//先利用keys对数组排序，目的是把目标数组的key排好序
    reset($valArr); //指针指向数组第一个值 
    foreach($valArr as $key=>$value) {
        $newArr[$key] = $array[$key];
    }
    return $newArr;
}


/**
 * @author  二维数组转换一维数组
 * @param   array 需要转换的数组
 */
function multi2array($array) {  
    static $result_array = array();  
    foreach ($array as $key => $value) {  
        if (is_array($value)) {  
           multi2array($value);  
       }  
       else{
           $result_array[] = $value;
       }    
   }  
   return $result_array;  
}


/**
 * @author [PHP根据键值，    对二维数组重新进行分组]
 * @param  [type] $array    [二维数组]
 * @param  [type] $key      [键名]
 * @return [type]           [新的二维数组]
 */
function array_group_by($arr, $key){
    $grouped = array();
    foreach ($arr as $value) {
        $grouped[$value[$key]][] = $value;
    }
    if (func_num_args() > 2) {
        $args = func_get_args();
        foreach ($grouped as $key => $value) {
            $parms = array_merge($value, array_slice($args, 2, func_num_args()));
            $grouped[$key] = call_user_func_array('array_group_by', $parms);
        }
    }
    return $grouped;
} 


/**
 * @author 将字符串转为数组
 * @param string
 */
function parse_config_attr($string) {
    $str = "/[,;\r\n ]+/";
    $array = preg_split($str, trim($string, ",;\r\n"));
    if(strpos($string,':')){
        $value  =   array();
        foreach ($array as $val) {
            list($k, $v) = explode(':', $val);
            $value[$k]   = $v;
        }
    }elseif (strpos($string,'--')) {
        $value  =   array();
        foreach ($array as $val) {
            list($k, $v) = explode('--', $val);
            $value[$k]   = $v;
        }
    }else{
        $value  =   $array;
    }
    return $value;
}

// -----------------------------------------数组---end------------------------------------------------------



// -----------------------------------------视图设置---start------------------------------------------------------

// 显示图片，如果图片不存在，显示默认图片
function show_image($url = '') {
    if(empty($url)){
        // 地址为空，显示默认图片
        return '/static/images/photo.png';
    }else{
        // 地址不为空，判断是远程还是本地文件
        if(strpos($url, 'http') !== false){ // 位置可以取 0
            if(remote_file_exists($url)){
                return $url;
            }else{
                // 图片不存在，显示默认图片
                return '/static/images/photo.png';
            }
        }else{
            // 本地文件
            if(file_exists('.' . $url)){
                return $url;
            }else{
                // 图片不存在，显示默认图片
                return '/static/images/photo.png';
            }
        }
    }
}


// curl 判断远程文件是否存在，返回 true 或 false
function remote_file_exists($url) {
    $curl = curl_init($url);
    // 不取回数据
    curl_setopt($curl, CURLOPT_NOBODY, true);
    // 发送请求
    $result = curl_exec($curl);
    
    $found = false;
    if($result !== false) {
        // 检查http响应码是否为200
        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if($statusCode == 200){
            $found = true;
        }
    }
    curl_close($curl);
    return $found;
}


/**
 * 获取图片的Base64编码
 * @date 2017-02-20 19:41:22
 *
 * @param $url 传入图片地址
 * @param $type 返回类型
 *
 * @return string
 */
function curl_url($url,$type=0,$timeout=30){  
      
    $msg = ['code'=>2100,'status'=>'error','msg'=>'未知错误！'];  
    $imgs= ['image/jpeg'=>'jpeg',  
               'image/jpg'=>'jpg',  
               'image/gif'=>'gif',  
               'image/png'=>'png',  
               'text/html'=>'html',  
               'text/plain'=>'txt',  
               'image/pjpeg'=>'jpg',  
               'image/x-png'=>'png',  
               'image/x-icon'=>'ico'  
         ];  
    if(!stristr($url,'http')){  
        $msg['code']= 2101;  
        $msg['msg'] = 'url地址不正确!';    
        return $msg;  
    }     
    $dir= pathinfo($url);  
    //var_dump($dir);  
    $host = $dir['dirname'];  
    $refer= $host.'/';  
    $ch = curl_init($url);  
    curl_setopt ($ch, CURLOPT_REFERER, $refer); //伪造来源地址  
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//返回变量内容还是直接输出字符串,0输出,1返回内容  
    curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);//在启用CURLOPT_RETURNTRANSFER的时候，返回原生的（Raw）输出  
    curl_setopt($ch, CURLOPT_HEADER, 0); //是否输出HEADER头信息 0否1是  
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout); //超时时间  
    $data = curl_exec($ch);  
    //$httpCode = curl_getinfo($ch,CURLINFO_HTTP_CODE);   
    //$httpContentType = curl_getinfo($ch,CURLINFO_CONTENT_TYPE);  
    $info = curl_getinfo($ch);  
    curl_close($ch);  
    $httpCode = intval($info['http_code']);  
    $httpContentType = $info['content_type'];  
    $httpSizeDownload= intval($info['size_download']);  
      
    if($httpCode!='200'){  
        $msg['code']= 2102;  
        $msg['msg'] = 'url返回内容不正确！';  
        return $msg;  
    }  
    if($type>0 && !isset($imgs[$httpContentType])){  
        $msg['code']= 2103;  
        $msg['msg'] = 'url资源类型未知！';  
        return $msg;  
    }  
    if($httpSizeDownload<1){  
        $msg['code']= 2104;  
        $msg['msg'] = '内容大小不正确！';  
        return $msg;  
    }  
    $msg['code']  = 200;  
    $msg['status']='success';  
     $msg['msg']   = '资源获取成功';  
    if($type==0 or $httpContentType=='text/html') $msg['data'] = $data;  
    $base_64 = base64_encode($data);  
    if($type==1) $msg['data'] = $base_64;  
    elseif($type==2) $msg['data'] = "data:{$httpContentType};base64,{$base_64}";  
    elseif($type==3) $msg['data'] = "<img src='data:{$httpContentType};base64,{$base_64}' />";  
    else $msg['msg'] = '未知返回需求！';     
    unset($info,$data,$base_64);  
    return $msg;  
  
}

//本地转base64
function base64EncodeImage ($image_file) {
    
    if(file_exists($image_file) || is_file($image_file)){

        $base64_image = '';

        $image_info = getimagesize($image_file);

        $image_data = fread(fopen($image_file, 'r'), filesize($image_file));

        $base64_image = 'data:' . $image_info['mime'] . ';base64,' . chunk_split(base64_encode($image_data));

        return $base64_image;

    }

    else{

        return false;

    }
    
}

//网络转base64
function imgtobase64($img='', $imgHtmlCode=true)
{
    $imageInfo = getimagesize($img);
    $base64 = "" . chunk_split(base64_encode(file_get_contents($img)));
    return 'data:' . $imageInfo['mime'] . ';base64,' . chunk_split(base64_encode(file_get_contents($img)));;
}


// -----------------------------------------视图设置---end------------------------------------------------------




// -----------------------------------------数据库设置---start------------------------------------------------------
/**
 * @author  设置表类型转换
 * @param   $str [要转换的类型]
 * @param   $name [数据表的name值]
 */
function settingType ($str, $name) {

    $spec = model('Setting')->where(["name"=>"{$name}"])->value('values');
    $lists = parse_config_attr($spec);
    $code = "";
    foreach ($lists as $key => $value) {
        switch ($str) {
            case "{$key}":
                $code = $value;
                break;
        }
    }
    return $code;
}




// -----------------------------------------数据库设置---end------------------------------------------------------






// ------------------------------------------字符串---start-------------------------------------------------------

/**
 * @author 截取字符串长度
 * @param $string 要截取的字符串
 * @param $sublen 截取数量
 * @param $start  开始长度默认为 0
 */
function cutStr($string, $sublen, $start = 0, $code = 'UTF-8'){
    if($code == 'UTF-8'){
        $pa = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|\xe0[\xa0-\xbf][\x80-\xbf]|[\xe1-\xef][\x80-\xbf][\x80-\xbf]|\xf0[\x90-\xbf][\x80-\xbf][\x80-\xbf]|[\xf1-\xf7][\x80-\xbf][\x80-\xbf][\x80-\xbf]/";
        preg_match_all($pa, $string, $t_string);
        if(count($t_string[0]) - $start > $sublen)
            return join('', array_slice($t_string[0], $start, $sublen))."...";
            return join('', array_slice($t_string[0], $start, $sublen));
    }else{ 
        $start = $start*2;
        $sublen = $sublen*2;
        $strlen = strlen($string);
        $tmpstr = '';
        for($i=0; $i<$strlen; $i++){
            if($i>=$start && $i<($start+$sublen)){
                if(ord(substr($string, $i, 1))>129){
                    $tmpstr.= substr($string, $i, 2);
                }else{ 
                    $tmpstr.= substr($string, $i, 1);
                }
            } 
            if(ord(substr($string, $i, 1))>129) $i++;
        } 
    if(strlen($tmpstr)<$strlen ) $tmpstr.= "...";
    return $tmpstr;
    }
}


/*
** 随机字符串生成
** @param int $len 生成的字符串长度
** @return string
*/
function random_string($len = 6) {

    $chars = array(
            'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k',
            'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v',
            'w', 'x', 'y', 'z', 'A', 'B', 'C', 'D', 'E', 'F', 'G',
            'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R',
            'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', '0', '1', '2',
            '3', '4', '5', '6', '7', '8', '9'
        );
    
    $charsLen = count($chars) - 1;
    shuffle($chars); // 将数组打乱
    
    $output = '';
    for($i = 0; $i < $len; $i++){
        $output .= $chars[mt_rand(0, $charsLen)];
    }
    return $output;
}



/**
 * 订单号
 */
function orderNum () {

    return mt_rand(0,10) . date('Ymd') . mt_rand(0,10) . date('His');

}



/**
 * 把字节转换MB
 */
function sizecount($filesize) {
    if($filesize >= 1073741824) {
        $filesize = round($filesize / 1073741824 * 100) / 100 . ' GB';
    } elseif($filesize >= 1048576) {
        $filesize = round($filesize / 1048576 * 100) / 100 . ' MB';
    } elseif($filesize >= 1024) {
        $filesize = round($filesize / 1024 * 100) / 100 . ' KB';
    } else {
        $filesize = $filesize . ' B';
    }
    return $filesize;
}

// ------------------------------------------字符串---end-------------------------------------------------------



// ------------------------------------------时间---start-------------------------------------------------------

/**
 * 将秒数转换成时分秒
 */
function changeTimeType($seconds){
    if ($seconds >3600){
       $hours =intval($seconds/3600);
       $minutes = $seconds % 3600;
       $time = $hours.":".gmstrftime('%M:%S',$minutes);
    }else{
       $time = gmstrftime('%H:%M:%S',$seconds);
    }
    if (substr($time, 0, 2) == '00') {
        $time = substr($time, 3);
    }
    
    return$time;
}


/**
 * 根据时间显示刚刚,几分钟前,几小时前
 */
function tranTime($time)
{
    $rtime = date("m-d H:i",$time);
    $htime = date("H:i",$time);
          
    $time = time() - $time;
          
    if ($time < 60)
    {
        $str = '刚刚';
    }
    elseif ($time < 60 * 60)
    {
        $min = floor($time/60);
        $str = $min.'分钟前';
    }
    elseif ($time < 60 * 60 * 24)
    {
        $h = floor($time/(60*60));
        $str = $h.'小时前 '; //.$htime;
    }
    elseif ($time < 60 * 60 * 24 * 3)
    {
        $d = floor($time/(60*60*24));
        if($d==1)
            $str = '昨天 ';//.$rtime;
        else
            $str = '前天 ';//.$rtime;
    }
    else
    {
        $str = $rtime;
    }
    return $str;
}   

/* 
** 浏览记录按日期分组
*/
function groupVisit($visit)
{
    $curyear = date('Y');
    $visit_list = [];
    foreach ($visit as $v) {
        if ($curyear == date('Y', $v['visittime'])) {
            $date = date('m月d日', $v['visittime']);
        } else {
            $date = date('Y年m月d日', $v['visittime']);
        }
        $visit_list[$date][] = $v;
    }
    return $visit_list;
}



// ------------------------------------------时间---end-------------------------------------------------------
