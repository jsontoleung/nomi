// JavaScript Document
   //锁定屏幕滑动

   var canTouch = 0;

   document.addEventListener("touchmove", function (e) {

   if (canTouch == 0) {

       // e.preventDefault();

       e.stopPropagation();

   }
   }, false);



   // 禁止安卓图片点击放大

   document.addEventListener("click",function (e) {

   if(e.target.nodeName=="IMG"){

       e.preventDefault();

   }

   });

   //苹果回弹背景
   var boo=true;
   $("input").focus(function(){
    
    boo=false;
    
    setTimeout(function(){
    boo=true;	
    },20)
   })

   $("input").blur(function(){
    
      setTimeout(function(){
      if(boo){
      $('html,body').animate({scrollTop:0}, 0);
      }
   },10)
       
   });

//    $("#age,#province,#city,#trade,#date").focus(function(){
    
//     boo=false;
    
//     setTimeout(function(){
//     boo=true;	
//     },20)
//    })

//    $("#age,#province,#city,#trade,#date").blur(function(){
    
//       setTimeout(function(){
//       if(boo){
//       $('html,body').animate({scrollTop:0}, 0);
//       }
//    },10)
       
//    });


// 手机键盘弹出时的适配
var phoneHeight = document.documentElement.clientHeight; //获取当前页面高度
   phoneWidth = document.documentElement.clientWidth;

winHeight = $(window).height(); //获取当前页面高度

$(window).resize(function () {

  var thisHeight = $(this).height();

  if (winHeight - thisHeight > 50) {
            $(".login .title").css({
             "top":"0%"
            })
            if(phoneHeight/phoneWidth<1.58){
                $(".login .title").css({
                    "top":"-10%"
                })
            }
  }else{
    $(".login .title").css({
        "top":"15%"
    })
  }
});

// $(".login .btn").click(function () {
//    var zh_txt=$(".zhanghao input").val()
//    var mm_txt=$(".mima input").val()
// if(zh_txt==""){
// 	layui.use(['layer', 'form'], function() {
//         var layer = layui.layer,
//             form = layui.form;
//         layer.msg('账号不能为空', {
//             area: ['50%', 'auto']
//         });

//     });

//     return false;
// }else if(mm_txt==""){
// 	layui.use(['layer', 'form'], function() {
//         var layer = layui.layer,
//             form = layui.form;
//         layer.msg('密码不能为空', {
//             area: ['50%', 'auto']
//         });

//     });

//     return false;
// }else{
    
// }

   
// })