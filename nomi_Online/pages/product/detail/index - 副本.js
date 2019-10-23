// pages/product/detail/index.js
var WxParse = require('../../../wxParse/wxParse.js');
var util = require('../../../utils/util.js');
var app = getApp();

Page({

  /**
   * 页面的初始数据
   */
  data: {
        buy_num: 1,
        showModal: false,
        prouid: false,
        shareImage:'',
        maskHidden:true,

        imagePath: "",//生成图片的临时路径
        pathbg: "/images/wi.png",//白色背景
        path: "",//产品图片
        imageZw: "/images/appointment_15362929111571033421.jpg",//小程序码或者二维码
        imageTx: "/images/appointment_15362929111571033421.jpg",//头像
        // imageEwm: "/images/wx_login.png",
        maskHidden: true,
        pro_id:'',
        name:'',

  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {

        var that = this;
        //产品ID
        var pro_id = options.pro_id;
        // 把产品ID赋值出去，其他函数可以接收
        this.data.currentPostId = pro_id;

        // 分享
        var prouid = options.prouid;
        if (pro_id && prouid) {
            wx.redirectTo({
                url: "/pages/login/login?prouid=" + prouid + "&pro_id=" + pro_id
            })
        } else {
            prouid = false;
        }

        wx.request({
            url: app.data.getUrl + "/product/detail",
            method: 'post',
            data: {
                pro_id: pro_id,
                uid: wx.getStorageSync('uid')
            },
            header: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            success: function (res) {

                if (res.data.status == 1) {
                    
                    var lunbo = res.data.lunbo;
                    var region = res.data.region;
                    var comment = res.data.comment;
                    var pro = res.data.pro;
                    var other = res.data.other;
                    var is_collect = res.data.is_collect;
                    var content = pro.content;
                    WxParse.wxParse('content', 'html', content, that, 3);

                    that.setData({
                        path:lunbo[0],
                        lunbo: lunbo,
                        region: region,
                        comment: comment,
                        pro: pro,
                        other: other,
                        collected: is_collect,
                        prouid: prouid,
                        pro_id: pro_id
                    });

                } else {
                    wx.showToast({
                        title: res.data.msg,
                        duration: 2000
                    });
                }

            },
            fail: function (e) {
                wx.showToast({
                    title: '网络异常',
                    duration: 2000
                });
            },
        })
        //产品ID
        // var that = this;
        // var pro_id = that.data.pro_id;
        wx.request({
          url: app.data.getUrl + "/Wxqrcode/proQrcode",
          method: 'post',
          data: {
            pro_id: pro_id,
            uid: wx.getStorageSync('uid')
          },
          header: {
            'Content-Type': 'application/x-www-form-urlencoded'
          },
          success: function (res) {
            if (res.data.status == 1) {
              that.setData({
                name: res.data.data.name,
              });
              wx.getImageInfo({
                src: res.data.data.photo,
                success(res) {
                  that.setData({
                    path: res.path,
                  });
                }
              })
              var fsm = wx.getFileSystemManager();  //文件管理器
              var buffer = wx.base64ToArrayBuffer(res.data.data.headimg); //base 转二进制
              var FILE_BASE_NAME = 'tmp_base64src' + '_' + Date.parse(new Date()); //文件名
              var format = 'png'; //文件后缀
              var filePath = `${wx.env.USER_DATA_PATH}/www.${format}`; //文件名
              fsm.writeFile({ //写文件
                filePath,
                data: buffer,
                encoding: 'binary',
                success(res) {
                  wx.getImageInfo({
                    src: filePath,
                    success(res) {
                      that.setData({
                        imageTx: res.path,
                      });
                    }
                  })
                }
              })
              
              wx.getImageInfo({
                src: res.data.data.qrcode,
                success(res) {
                  that.setData({
                    imageZw: res.path,
                  });
                }
              })
              // that.setData({
              //   path: res.data.data.photo,
              //   imageTx: res.data.data.headimg,
              //   imageZw: res.data.data.qrcode,
              // });
            } else {
              wx.showToast({
                title: res.data.msg,
                duration: 2000
              });
            }
          },
          fail: function (e) {
            wx.showToast({
              title: '网络异常',
              duration: 2000
            });
          },
        })
    },





    /**
     * 加入我们显示
     */
    popupShow: function (options) {
        this.setData({
            showModal: true
        })
    },




    /**
    * 加入我们隐藏
    */
    popupHide: function () {
        this.setData({
            showModal: false
        })
    },




    /**
     * 购买会员
     */
    buyUser: function (e) {

        var leve_id = e.currentTarget.dataset.type;

        wx.navigateTo({
            url: "/pages/user/addmine/index?level=" + leve_id
        })


    },




    /* 点击减号 */
    bindMinus: function () {

        var buy_num = this.data.buy_num;

        // 如果大于1时，才可以减  
        if (buy_num > 1) {
            buy_num--;
        }

        // 将数值与状态写回  
        this.setData({
            buy_num: buy_num,
        });

    },

    /* 点击加号 */
    bindPlus: function () {

        var buy_num = this.data.buy_num;

        // 不作过多考虑自增1
        if (buy_num >= this.data.pro.current_cnt) {
            wx.showToast({
                title: '库存有限喔!',
                duration: 1000
            })
        } else {
            buy_num++;
        }

        // 将数值与状态写回  
        this.setData({
        buy_num: buy_num
        });

    },

    /* 输入框事件 */
    bindManual: function (e) {

        var buy_num = e.detail.value;

        // 将数值与状态写回  
        this.setData({
        buy_num: buy_num
        });

    },


    /**
     * 收藏
     */
    onCollectionTap: function (event) {
        
        var that = this;
        // 产品id
        var proid = event.currentTarget.dataset.postid;
        //收藏状态
        var collected = event.currentTarget.dataset.type;
        
        wx.request({

            url: app.data.getUrl + "/product/collect",
            method: 'post',
            data: {
                proid: proid,
                collected: collected,
                uid: wx.getStorageSync('uid')
            },
            header: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },

            success: function (res) {

                if (res.data.status == 1) {

                    that.setData({
                        collected: res.data.collected
                    });

                }
            },
            fail: function (e) {
                wx.showToast({
                    title: '网络异常',
                    duration: 2000
                });
            },
        })
            
    },



    /**加入购物车 */
    onAddcar:function (e) {

        util.isLogin(wx.getStorageSync('uid'));
        var that = this;
    
        if (that.data.pro.type == 1) {
            wx.showToast({
                title: '线下服务不能加入购物车',
                icon: "none",
                duration: 1500
            })
            return false;
        }

        var proid = this.data.currentPostId;//产品ID
        var buy_num = that.data.buy_num;//加入购物车数量
        var price = that.data.pro.price_after;//产品价格
        
        wx.request({

            url: app.data.getUrl + "/order/addCar",
            method: 'post',
            data: {
                proid: proid,
                buy_num: buy_num,
                price: price,
                uid: wx.getStorageSync('uid')
            },
            header: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },

            success: function (res) {

                if (res.data.status == 1) {

                    wx.showToast({
                        title: res.data.msg,
                        icon: 'success',
                        duration: 2000
                    });

                } else {
                    wx.showToast({
                        title: res.data.msg,
                        duration: 2000
                    });
                }
            },
            fail: function (e) {
                wx.showToast({
                    title: '网络异常',
                    duration: 2000
                });
            },
        })

    },



    /**
     * 跳转支付页面
     */
    onPayTap:function(e) {

        util.isLogin(wx.getStorageSync('uid'));

        var that = this;

        var proid = this.data.currentPostId;//产品ID

        var buy_num = that.data.buy_num;

      wx.setStorageSync('now_buy_num', buy_num)

        wx.navigateTo({
            url: "/pages/product/pay/index?proid=" + proid
        })

    },




    /** 跳转购物车 */
    addCar: function () {
        util.isLogin(wx.getStorageSync('uid'));
        wx.redirectTo({
            url: "/pages/order/car/index"
        })
    },

    

    /** 分享 */
    onShareAppMessage: function (options) {

        util.isLogin(wx.getStorageSync('uid'));

        let that = this;
        //自定义信息
        let sendinfo = {
            id: options.target.dataset.id,
            title: options.target.dataset.title,
            userid: wx.getStorageSync('uid')
        }
        let str = JSON.stringify(sendinfo);

        if (options.from == 'button') {
            console.log('按钮分享');
            that.shareReturn(sendinfo.id);
        }

        return {

            title: sendinfo.title,
            path: '/pages/product/detail/index?pro_id=' + sendinfo.id + '&prouid=' + sendinfo.userid,
            // path: '/pages/index/index?shareid=' + sendinfo.userid,
            success: (res) => {

                wx.showToast({
                    title: '分享成功',
                    icon: 'success',
                    duration: 2000
                });

                console.log("分享success()");
                console.log("onShareAppMessage()==>>转发成功", res);

            },
            fali: function (res) {
                // 分享失败
                console.log("onShareAppMessage()==>>转发失败", res);
            }

        }

    },




    // 分享回调
    shareReturn: function (id) {

        wx.request({

            url: app.data.getUrl + "/product/share",
            method: 'post',
            data: {
                id: id,
                uid: wx.getStorageSync('uid')
            },
            header: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },

            success: function (res) {

                if (res.data.status == 1) {

                    console.log('回调成功');

                } else {
                    wx.showToast({
                        title: res.data.msg,
                        duration: 2000
                    });
                }
            },
            fail: function (e) {
                wx.showToast({
                    title: '网络异常',
                    duration: 2000
                });
            },
        })

    },
    // 分享
  sharep:function(e){
    // 页面初始化 options为页面跳转所带来的参数
    // var size = this.setCanvasSize();//动态设置画布大小
    this.createNewImg();
    //创建初始化图片
  },
  // 绘制分享的图片
  //适配不同屏幕大小的canvas    生成的分享图宽高分别是 750  和940，老实讲不知道这块到底需不需要，然而。。还是放了，因为不写这块的话，模拟器上的图片大小是不对的。。。
     setCanvasSize: function() {
         var size = {};
         try {
             var res = wx.getSystemInfoSync();
             var scale = 750;//画布宽度
             var scaleH = 940 / 750;//生成图片的宽高比例
             var width = res.windowWidth-120;//画布宽度
             var height = res.windowWidth * scaleH-50;//画布的高度
             size.w = width;
             size.h = height;
      
    } catch (e) {
             // Do something when catch error
             console.log("获取设备信息失败" + e);
      
    }
         return size;
    
  },
  //将1绘制到canvas的固定
  settextFir0: function (context) {
    var that = this;
    var size = that.setCanvasSize();
    var textFir = that.data.name;
    console.log(textFir);
    context.setFontSize(10);
    context.setTextAlign("center");
    context.setFillStyle("#999");
    context.fillText(textFir, size.w / 1.73, size.h * 0.08);
    context.stroke();
  },
     //将1绘制到canvas的固定
    settextFir: function (context) {
         var that = this;
         var size = that.setCanvasSize();
         var textFir = "又有好货了";
         console.log(textFir);
         context.setFontSize(12);
         context.setTextAlign("center");
         context.setFillStyle("#666");
         context.fillText(textFir, size.w / 1.6, size.h * 0.12);
         context.stroke();
  },
  //将1绘制到canvas的固定
  settextFir2: function (context) {
    var that = this;
    var size = that.setCanvasSize();
    var textFir =  that.data.pro.name;
    console.log(textFir);
    context.setFontSize(10);
    context.setTextAlign("left");
    context.setFillStyle("#666");
    context.fillText(textFir, 80 , size.h*0.66);
    context.stroke();
  },
  //将1绘制到canvas的固定
  settextFir3: function (context) {
    var that = this;
    var size = that.setCanvasSize();
    var textFir = "折后价：";
    console.log(textFir);
    context.setFontSize(12);
    context.setTextAlign("left");
    context.setFillStyle("#666");
    context.fillText(textFir, 80, size.h * 0.72);
    context.stroke();
  },
  //将1绘制到canvas的固定
  settextFir4: function (context) {
    var that = this;
    var size = that.setCanvasSize();
    var textFir = '￥' +parseFloat(that.data.pro.price_after);
    console.log(textFir);
    context.setFontSize(16);
    context.setTextAlign("left");
    context.setFillStyle("#e8396a");
    context.fillText(textFir, 120, size.h * 0.72);
    context.stroke();
  },
  //将1绘制到canvas的固定
  settextFir5: function (context) {
    var that = this;
    var size = that.setCanvasSize();
    var textFir = 'NOMYA';
    console.log(textFir);
    context.setFontSize(14);
    context.setTextAlign("left");
    context.setFillStyle("#e8396a");
    context.fillText(textFir, 80, size.h * 0.28);
    context.stroke();
  },
  //将1绘制到canvas的固定
  settextFir6: function (context) {
    var that = this;
    var size = that.setCanvasSize();
    var textFir = '美生态 轻投入';
    console.log(textFir);
    context.setFontSize(10);
    context.setTextAlign("left");
    context.setFillStyle("#666");
    context.fillText(textFir, 110, size.h * 0.92);
    context.stroke();
  },
  //将1绘制到canvas的固定
  settextFir7: function (context) {
    var that = this;
    var size = that.setCanvasSize();
    var textFir = '携手共创健康美丽生活';
    console.log(textFir);
    context.setFontSize(10);
    context.setTextAlign("left");
    context.setFillStyle("#666");
    context.fillText(textFir, 70, size.h * 0.97);
    context.stroke();
  },
  //将1绘制到canvas的固定
  settextFir8: function (context) {
    var that = this;
    var size = that.setCanvasSize();
    var textFir = '糯米芽  ';
    console.log(textFir);
    context.setFontSize(12);
    context.setTextAlign("left");
    context.setFillStyle("#e8396a");
    context.fillText(textFir, 70, size.h * 0.92);
    context.stroke();
  },
  //将1绘制到canvas的固定
  settextFir9: function (context) {
    var that = this;
    var size = that.setCanvasSize();
    var textFir = '￥' + parseFloat(that.data.pro.price_after);
    console.log(textFir);
    context.setFontSize(10);
    context.setTextAlign("center");
    context.setFillStyle("#666");
    context.fillText(textFir, 200, size.h * 0.72);
    context.stroke();
  },
     //将2绘制到canvas的固定
    settextSec: function (context) {
         var that = this;
         var size = that.setCanvasSize();
         var textSec = "长按识别查看商品";
         context.setFontSize(10);
         context.setTextAlign("center");
         context.setFillStyle("#666");
         context.fillText(textSec, size.w, size.h * 0.97);
         context.stroke();
  },
     //将canvas转换为图片保存到本地，然后将图片路径传给image图片的src
    createNewImg: function () {
         var that = this;
         var size = that.setCanvasSize();
         var context = wx.createCanvasContext('myCanvas');
         var path = that.data.path;
         var pathbg = that.data.pathbg;
         var imageTx = that.data.imageTx;
        //  var imageEwm = that.data.imageEwm;
         var imageZw = that.data.imageZw;

         context.drawImage(pathbg, 60, 0, size.w, size.h);
         context.drawImage(path, 80, 125, size.w-40, size.h*0.3);
         context.drawImage(imageTx, 80, 20, size.w * 0.14, size.w * 0.14);
        //  context.drawImage(imageEwm, size.w / 2 - 60, size.h * 0.32, size.w * 0.33, size.w * 0.33);
        //  context.drawImage(imageZw, size.w / 2, size.h * 0.78, size.w * 0.2, size.w * 0.2);
        context.drawImage(imageZw, size.w-25, size.h * 0.8,size.w * 0.2, size.w * 0.2);

         this.settextFir0(context);
         this.settextFir(context);
         this.settextFir2(context);
         this.settextFir3(context);
         this.settextFir4(context);
         this.settextFir5(context);
         this.settextFir6(context);
         this.settextFir7(context);
         this.settextFir8(context);
         this.settextFir9(context);
         this.settextSec(context);
         console.log(size.w, size.h)
         //绘制图片
        wx.showToast({
          title: '生成中...',
          icon: 'loading',
          duration: 2000
        });
      context.draw(false,function(){
           setTimeout(function () {
             wx.canvasToTempFilePath({
               x: 0,
               y: 0,
               canvasId: 'myCanvas',
               success: function (res) {
                 var tempFilePath = res.tempFilePath;
                 console.log(tempFilePath);
                 that.setData({
                   imagePath: tempFilePath,
                   canvasHidden: false,
                   maskHidden: true,
                 });
                 //将生成的图片放入到《image》标签里
                 var img = that.data.imagePath;
                 wx.previewImage({
                   current: img, // 当前显示图片的http链接
                   urls: [img] // 需要预览的图片http链接列表
                 })
                  // wx.saveImageToPhotosAlbum({
                  //     filePath: tempFilePath,
                  //     success: function (res) {
                  //       wx.showToast({
                  //         title: '分享图片已保存到相册,请到朋友圈选择图片发布'
                  //       })
                  //     }
                  //   })
               },
               fail: function (res) {
                 console.log(res);

               }
             },2000);
         });
    }, 2000);
    
  },

})