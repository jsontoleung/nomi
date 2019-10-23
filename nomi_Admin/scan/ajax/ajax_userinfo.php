<?php
include('header_ajax.php');
include('QRcode.php');
$qrcode = new QRcode();

if($_POST['ph_num']!=''){
	$telphone=$_POST['ph_num'];
	$make_time=$_POST['ph_time'];
	$utimein=$_POST['ph_in'];
}else{
	// 判断是否存在手机号码
	if(!$_SESSION['telphone']){
		$result['code']=1;
	    $result['msg']='手机号码不存在';
	    echo json_encode($result);die;
	}
	$telphone=$_SESSION['telphone'];
	$make_time=$_SESSION['make_time'];
	$utimein=$_SESSION['utimein'];
	// 判断数量大于1
	$sql="SELECT count(*) as m from `userinfo` where telphone='$telphone' and is_cancel=0";
	$userinfo_num = find_one($sql);
	if($userinfo_num['m']>1){
		$result['code']=1;
	    $result['msg']='多条数据';
	    echo json_encode($result);die;
	}
}

$sql="SELECT * from `userinfo` where telphone='$telphone' and make_time='$make_time' and utimein='$utimein' and is_cancel=0";
$userinfo = find_one($sql);
if(empty($userinfo)){
	$result['code']=1;
    $result['msg']='用户不存在';
    echo json_encode($result);die;
}
$userinfo['sex']=$userinfo['sex']==1?'男':'女';
if($userinfo['is_off']==1){
	// 已核销
	$value3['sex']=$userinfo['sex']==1?'男':'女';
	$value3['age']=$userinfo['age'];
	$value3['profession']=$userinfo['industry'];
	$value2=json_encode($value3);
	$path = "upload/".'institution_off_'.$userinfo['id'].'.jpg';
}else{
	// 未核销
    $value2=$code_url.'zfb.php?uid='.$userinfo['id'];
    $path = "upload/".'institution_'.$userinfo['id'].'.jpg';
}
// 生成二维码
$errorCorrectionLevel = 'H';    //容错级别  
$matrixPointSize = 6;           //生成图片大小 
// $path = "upload/".'institution_'.$userinfo['id'].'.jpg';
ob_start();//开启缓冲区
QRcode::png($value2, false, 'H', 6, 2);//生成二维码
$img = ob_get_contents();//获取缓冲区内容
ob_end_clean();//清除缓冲区内容
$imgInfo = 'data:png;base64,' . chunk_split(base64_encode($img));//转base64
ob_flush();
$result['code']=0;
$result['userinfo']=$userinfo;
$result['qrcode']=$imgInfo ;
echo json_encode($result);die;

?>