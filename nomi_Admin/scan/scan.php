<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="apple-touch-fullscreen" content="YES" />
<meta name="format-detection" content="telephone=no" />
<meta name="apple-mobile-web-app-capable" content="yes" />
<meta name="apple-mobile-web-app-status-bar-style" content="black" />
<meta name="viewport" content="width=device-width, initial-scale=1.0,minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">

<meta content="telephone=no" name="format-detection" />
<meta content="email=no" name="format-detection" />
<meta http-equiv="Expires" content="-1" />
<meta http-equiv="pragram" content="no-cache" />
<title>登录页</title>
<script type="text/javascript" src="js/jquery-1.8.2.min.js"></script>
<script type="text/javascript" src="js/jquery.cookie.js"></script>
<script src="https://res.wx.qq.com/open/js/jweixin-1.1.0.js"></script>
<link rel="stylesheet" type="text/css" href="css/style.css"/>
<link rel="stylesheet" type="text/css" href="css/animate.min.css"/>
<script src="layui/layui.js"></script>
<link rel="stylesheet" href="layui/css/layui.css">
<script src="https://gw.alipayobjects.com/as/g/h5-lib/alipayjsapi/3.1.1/alipayjsapi.inc.min.js"></script>
<script src="//g.alicdn.com/dingding/open-develop/1.6.9/dingtalk.js"></script>
</head>
<body>
<div class="page login">
    <img class="bg" src="img/baoming_bg.png" />
    <form id="staff_login">
        <div class="box">
            <div class="center">
                <img src="img/load_kuang.png" alt="">
                <div class="zhanghao">
                    <span>账号</span><input name="name" type="text" placeholder="请输入账号">
                    
                </div>
                <div class="mima">
                    <span>密码</span><input name="pwd" type="password" placeholder="请输入密码">
                    
                </div>
                <div class="btn">
                    <img src="img/load_btn.png" alt="" onclick="login_off()">
                </div>
            </div>
        </div>
    </form>
    <div class="saomiao hide" id="off_show">
        <img src="img/saomiao.png" alt="" id="J_btn_scanQR">
    </div>
</div>
</html>
<script src="js/login.js"></script>
<script>
var apiUrl="https://www.nomiyy.com/index.php/api/"; 
//微信分享
$.ajax({
    url:apiUrl+"staff/share" ,
    type: "post",
    data: {
        url: location.href.split('#')[0]
    },
    contentType: 'application/x-www-form-urlencoded;charset=utf-8',
    async: true,
    success: function (data) {
        wx.config({
            debug: false, // 开启调试模式,调用的所有api的返回值会在客户端alert出来，若要查看传入的参数，可以在pc端打开，参数信息会通过log打出，仅在pc端时才会打印。
            appId: data.appId, // 必填，公众号的唯一标识
            timestamp: data.timestamp, // 必填，生成签名的时间戳
            nonceStr: data.nonceStr, // 必填，生成签名的随机串
            signature: data.signature,// 必填，签名，见附录1
            jsApiList: ["scanQRCode"] // 必填，需要使用的JS接口列表，所有JS接口列表见附录2
        });
    }
});

if (/DingTalk/.test(window.navigator.userAgent)) { 
    // alert('钉钉客户端'); 
    var liu=1;
} else if (/AlipayClient/.test(window.navigator.userAgent)) { 
    // alert('支付宝客户端');
    var liu=2;
} else if(/MicroMessenger/.test(window.navigator.userAgent)) {
    // alert('微信浏览器');
    var liu=3;
}else{
    // alert('其他浏览器');
    var liu=4;
}
// 进来判断是否登录
if($.cookie('staff_login')==1){
    $('#off_show').css('display','block');
    $('#staff_login').css('display','none');
}else{
   $('#staff_login').css('display','block');
   $('#off_show').css('display','none');
}

var btnScanQR = document.querySelector('#J_btn_scanQR');
var btnScanBAR = document.querySelector('#J_btn_scanBAR');
btnScanQR.addEventListener('click', function(){
    if(liu==1){
        dd.ready(function() {
            dd.biz.util.scan({
                type: 'qrCode' , // type 为 all、qrCode、barCode，默认是all。
                onSuccess: function(res) {
                    //onSuccess将在扫码成功之后回调
                    /* data结构
                     { 'text': String}
                     */
                    if(!isNull(res.text)){
                        var wx_arr = eval("(" + res.text + ")");
                        // alert('钉钉提示-订单号：'+wx_arr.order_sn);
                        // 改订单状态
                        order_stat(wx_arr.order_sn);
                        // var uid=GetQueryString('uid',res.text);
                        // if(uid==''&&res.code!=''){
                        //     var model_arr = JSON.parse(res.code);
                        //     if(model_arr['sex']=='女'||model_arr['sex']=='男'){
                        //         layui.use(['layer', 'form'], function() {
                        //             var layer = layui.layer,
                        //                 form = layui.form;
                        //             layer.msg('该二维码已核销过', {
                        //                 area: ['50%', 'auto']
                        //             });
                        //         });
                        //         return false;
                        //     }
                        // }
                        
                        // $.ajax({
                        //     type:'post',
                        //     url:'ajax/ajax_off.php',
                        //     data:{'uid':uid},
                        //     dataType:'json',
                        //     success:function(data){
                        //         layui.use(['layer', 'form'], function() {
                        //             var layer = layui.layer,
                        //                 form = layui.form;
                        //             layer.msg(data['msg'], {
                        //                 area: ['50%', 'auto']
                        //             });
                        //         });
                        //         if(data['code']==2){
                        //             // 员工不存在
                        //             $('#staff_login').css('display','block');
                        //             $('#off_show').css('display','none');
                        //         }
                        //     }
                        // })
                    }
                },
                onFail : function(err) {

                }
            })
        });
    }
    if(liu==2){
        ap.scan(function(res){
            if(!isNull(res.code)){
                var wx_arr = eval("(" + res.code + ")");
                // ap.alert('支付宝提示-订单号：'+wx_arr.order_sn);
                // 改订单状态
                order_stat(wx_arr.order_sn);
            }
          ap.alert(res.code);
        });
    }
    if(liu==3){
        wx.ready(function () {
            wx.scanQRCode({
                needResult: 1,
                desc: 'scanQRCode desc',
                success: function (res) {
                    //扫码后获取结果参数赋值给Input
                    var url = res.resultStr;
                    var wx_arr = eval("(" + url + ")");
                    // alert('微信提示-订单号：'+wx_arr.order_sn);
                    // 改订单状态
                    order_stat(wx_arr.order_sn);
                }
            });
        });
    }
    if(liu==4){
        layui.use(['layer', 'form'], function() {
            var layer = layui.layer,
                form = layui.form;
            layer.msg('请在微信、支付宝或者钉钉打开', {
                area: ['50%', 'auto']
            });
        });
        return false;
    }
});
// 更改订单状态
function order_stat($order_sn){
    $.ajax({
        type:'post',
        url:apiUrl+'staff/orderstat',
        data:{'order_sn':$order_sn},
        dataType:'json',
        success:function(data){
            layui.use(['layer', 'form'], function() {
                var layer = layui.layer,
                    form = layui.form;
                layer.msg(data['msg'],{time:4000}, {
                    area: ['50%', 'auto']
                });
            });
            if(data['code']==2){
                // 员工不存在
                $('#staff_login').css('display','block');
                $('#off_show').css('display','none');
            }
        }
    })
}
// 员工登录
function login_off(){
    var zh_txt=$(".zhanghao input").val()
       var mm_txt=$(".mima input").val()
    if(zh_txt==""){
        layui.use(['layer', 'form'], function() {
            var layer = layui.layer,
                form = layui.form;
            layer.msg('账号不能为空', {
                area: ['50%', 'auto']
            });

        });

        return false;
    }else if(mm_txt==""){
        layui.use(['layer', 'form'], function() {
            var layer = layui.layer,
                form = layui.form;
            layer.msg('密码不能为空', {
                area: ['50%', 'auto']
            });

        });

        return false;
    }
    var staff_login=$('#staff_login').serialize();
    $.ajax({
      type:'post',
      url:apiUrl+'staff/login',
      data:staff_login,
      dataType:'json',
      success:function(data){
        if(data['code']==0){
            $.cookie('staff_login',1);
            // 登录成功
            $('#staff_login').css('display','none');
            $('#off_show').css('display','block');
        }else{
            // 登录失败
            layui.use(['layer', 'form'], function() {
                var layer = layui.layer,
                    form = layui.form;
                layer.msg(data['msg'], {
                    area: ['50%', 'auto']
                });

            });
        }
      }
  })
}
//判断字符是否为空的方法
function isNull(a){
    if(typeof a == "undefined" || a == null || a == ""){
        return true;
    }else{
        return false;
    }
}
// 采用正则表达式获取地址栏参数
function GetQueryString(name,url) {
    var reg = new RegExp("(^|\\?|&)"+ name +"=([^&]*)(\\s|&|$)", "i");
    if (reg.test(url)) return unescape(RegExp.$2.replace(/\+/g, " "));
    return "";
     // var reg = new RegExp("(^|&)"+ name +"=([^&]*)(&|$)");
     // var r = window.location.search.substr(1).match(reg);
     // if(r!=null)return  decodeURI(r[2]); return null;
}
</script>

