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
        act: '',
        tempFile: [],
        finishh: false,
        fx:true,
        shareallType:true,
        hideModal: true, //模态框的状态  true-隐藏  false-显示
        bnt:0
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
                act: res.data.data.act
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
    // 隐藏分享弹出
  hiddenShare:function(e){
      var that = this;
      that.setData({
        shareallType: true,
      })
  },
    // 点击分享
  shareAll:function(e){
    var that=this;
    that.setData({
      shareallType: false,
    })
  },
    // 分享朋友圈
  sharep:function(e){
    var that = this
    var path = that.data.path
    var pathbg = that.data.pathbg
    var imageTx = that.data.imageTx
    var imageZw = that.data.imageZw
    var act = that.data.act
    wx.showLoading({
      title: '生成中',
    })
    //设个定时器防止异步的问题
    setTimeout(function () {
      //初始化画布背景宽高及内容宽高变量
      // var avatarUrl = app.globalData.userInfo.avatarUrl
      var myCanvasWidth;
      var myCanvasHeight;
      var myCanvasWidth1;
      var myCanvasHeight1;
      var rpx;
      //获取手机屏幕尺寸并给画布宽高赋值
      wx.getSystemInfo({
        success: function (res) {
          console.log(res)
          // myCanvasWidth = res.windowWidth - 56
          // myCanvasHeight = res.windowHeight - 100
          myCanvasWidth = res.windowWidth;
          myCanvasHeight = res.windowHeight;
          myCanvasWidth1 = res.windowWidth;
          myCanvasHeight1 = res.windowHeight;
          rpx = res.windowWidth / 375
        },
      })
      console.log(myCanvasWidth, myCanvasHeight)
      console.log("宽：" + parseInt(myCanvasWidth / 5), "高:" + parseInt(myCanvasHeight / 7.88))
      // var avatarurl_width = parseInt (myCanvasWidth / 5);    //绘制的头像宽度
      //将方形图片处理成圆形头像
      var avatarurl_heigth = parseInt(myCanvasHeight / 8);   //绘制的头像高度
      var avatarurl_width = avatarurl_heigth
      var avatarurl_x = myCanvasWidth / 2 - (avatarurl_width / 2);   //绘制的头像在画布上的位置
      var avatarurl_y = myCanvasHeight / 7 - (avatarurl_heigth / 2);   //绘制的头像在画布上的位置
      that.setData({
        canvasWidth: myCanvasWidth,
        canvasHeight: myCanvasHeight,
        canvasWidth1: myCanvasWidth1,
        canvasHeight1: myCanvasHeight1
      })
      //初始化画布
      // 使用 wx.createContext 获取绘图上下文 context
      var context = wx.createCanvasContext('firstCanvas')
      context.setFillStyle('#ffa71f')
      context.fillRect(25, 25, myCanvasWidth - 50, myCanvasHeight - 50)
      context.drawImage(pathbg, myCanvasWidth / 2 - ((myCanvasWidth - 50) / 2), myCanvasHeight / 2 - ((myCanvasHeight - 50) / 2), myCanvasWidth - 50, myCanvasHeight - 50)
      context.fill()
      // context.rect(0, 0, myCanvasWidth, myCanvasHeight)

      context.save();
      context.beginPath(); //开始绘制
      //先画个圆   前两个参数确定了圆心 （x,y） 坐标  第三个参数是圆的半径  四参数是绘图方向  默认是false，即顺时针
      context.arc(avatarurl_width / 2 - 110 * rpx + avatarurl_x, avatarurl_heigth / 2 + avatarurl_y, avatarurl_width / 2 - 10 * rpx, 0, Math.PI * 2, false);
      context.clip();//画好了圆 剪切  原始画布中剪切任意形状和尺寸。一旦剪切了某个区域，则所有之后的绘图都会被限制在被剪切的区域内 这也是我们要save上下文的原因

      context.drawImage(imageTx, avatarurl_x - 110 * rpx, avatarurl_y, avatarurl_width, avatarurl_heigth); // 推进去图片，必须是https图片
      context.restore(); //恢复之前保存的绘图上下文 恢复之前保存的绘图上下午即状态 还可以继续绘制
      context.setFontSize(14 * rpx)
      context.setTextAlign('left')
      context.setFillStyle('#666')
      context.fillText(that.data.name, myCanvasWidth / 2 - 70 * rpx, myCanvasHeight / 7.2)

      var strWidth = context.measureText(act).width;
      var ellipsis = '…';
      var ellipsisWidth = context.measureText(ellipsis).width;
      if (strWidth <= myCanvasWidth / 2 - 70 * rpx || myCanvasWidth / 2 - 70 * rpx <= ellipsisWidth) {
        var sss = act;
      } else {
        var len = act.length;
        while (strWidth >= myCanvasWidth / 2 - 70 * rpx - ellipsisWidth && len-- > 0) {
          act = act.slice(0, len);
          strWidth = context.measureText(act).width;
        }
        var sss = act + ellipsis;
      }
      context.setFontSize(16 * rpx)
      context.setTextAlign('left')
      context.setFillStyle('#333333')
      context.fillText(sss, myCanvasWidth / 2 - 70 * rpx, myCanvasHeight / 5.8)
      context.setFillStyle('red')

      context.setFontSize(18 * rpx)
      context.setTextAlign('center')
      context.setFillStyle('#e8396a')
      context.fillText("Nomya", myCanvasWidth / 2 - 110 * rpx, myCanvasHeight / 3.3)
      context.setFillStyle('red')

      // 产品图片
      context.drawImage(path, myCanvasWidth / 2 - 140 * rpx, myCanvasHeight / 3, myCanvasWidth / 2 + 90 * rpx, myCanvasHeight / 2 - 160 * rpx)
      context.restore(); //恢复之前保存的绘图上下文 恢复之前保存的绘图上下午即状态 还可以继续绘制

      context.setFillStyle('#666')
      context.setFontSize(16 * rpx)
      // context.fillText("折扣价：", myCanvasWidth / 2 - 110 * rpx, myCanvasHeight / 1.42)
      context.fillText("折扣价：", myCanvasWidth / 2 - 10 * rpx, myCanvasHeight / 1.42)

      context.setFillStyle('#e8396a')
      context.setFontSize(18 * rpx)
      // context.fillText("￥" + parseFloat(that.data.pro.price_after), myCanvasWidth / 2 - 60 * rpx, myCanvasHeight / 1.42)
      context.setTextAlign('left')
      context.fillText("￥" + parseFloat(that.data.pro.price_after), myCanvasWidth / 2 + 20 * rpx, myCanvasHeight / 1.42)
      context.setFillStyle('#666')
      context.setFontSize(16 * rpx)
      context.setTextAlign('left')
      context.fillText("原价：", myCanvasWidth / 2 - 140 * rpx, myCanvasHeight / 1.42)

      context.setFillStyle('#666')
      context.setFontSize(12 * rpx)
      context.setTextAlign('left')
      context.fillText("￥" + parseFloat(that.data.pro.price_before), myCanvasWidth / 2 - 100 * rpx, myCanvasHeight / 1.42)

      // context.setFillStyle('#666')
      // context.setFontSize(12 * rpx)
      // context.fillText("￥" + parseFloat(that.data.pro.price_before), myCanvasWidth / 2 + 10 * rpx, myCanvasHeight / 1.42)

      // context.moveTo(myCanvasWidth / 2 + 10 * rpx, myCanvasHeight / 1.42 - 13 * rpx);
      // context.lineTo(myCanvasWidth / 2 + 23 * rpx, myCanvasHeight / 1.42 + 5 * rpx);
      context.moveTo(myCanvasWidth / 2 - 100 * rpx, myCanvasHeight / 1.42 - 13 * rpx);
      context.lineTo(myCanvasWidth / 2 - 70 * rpx, myCanvasHeight / 1.42 + 5 * rpx);

      context.setStrokeStyle('#666')
      context.stroke();
      context.closePath();

      context.setFillStyle('#e8396a')
      context.setFontSize(16 * rpx)
      context.setTextAlign('center')
      context.fillText("糯米芽", myCanvasWidth / 2 - 120 * rpx, (myCanvasHeight) / 1.1)

      context.setFillStyle('#666')
      context.setFontSize(12 * rpx)
      context.fillText("  美生态 轻投入", myCanvasWidth / 2 - 60 * rpx, (myCanvasHeight) / 1.1)

      context.setFillStyle('#666')
      context.setFontSize(12 * rpx)
      context.fillText("携手共创健康美丽生活", myCanvasWidth / 2 - 84 * rpx, (myCanvasHeight) / 1.1 + 20 * rpx)

      /*小程序码*/
      context.drawImage(imageZw, myCanvasWidth / 2 - (myCanvasHeight / 10) + 125 * rpx, (myCanvasHeight) / 1.25, myCanvasHeight / 5 - 40 * rpx, myCanvasHeight / 5 - 30 * rpx)

      context.setFillStyle('#999')
      context.setFontSize(12 * rpx)
      context.setTextAlign('left')
      var text = that.data.pro.name;
      // context.fillText("武侯-红牌楼·1-3年·本科武侯-红牌楼·1-3年·本科武侯-红牌楼·1-3年·本科武侯-红牌楼·1-3年·本科武侯-红牌楼·1-3年·本科武侯-红牌楼·1-3年·本科", myCanvasWidth / 2 - 140 * rpx, myCanvasHeight / 1.58)
      var chr = text.split("");//这个方法是将一个字符串分割成字符串数组
      var temp = "";
      var row = [];
      for (var a = 0; a < chr.length; a++) {
        if (context.measureText(temp).width < 250 * rpx) {
          temp += chr[a];
        }
        else {
          a--; //这里添加了a-- 是为了防止字符丢失，效果图中有对比
          row.push(temp);
          temp = "";
        }
      }
      row.push(temp);
      //如果数组长度大于2 则截取前两个
      if (row.length > 2) {
        var rowCut = row.slice(0, 2);
        var rowPart = rowCut[1];
        var test = "";
        var empty = [];
        for (var a = 0; a < rowPart.length; a++) {
          if (context.measureText(test).width < 220 * rpx) {
            test += rowPart[a];
          }
          else {
            break;
          }
        }
        empty.push(test);
        var group = empty[0] + "..."//这里只显示两行，超出的用...表示
        rowCut.splice(1, 1, group);
        row = rowCut;
      }
      for (var b = 0; b < row.length; b++) {
        // context.fillText(row[b], 10, 30 + b * 30, 300);
        context.fillText(row[b], myCanvasWidth / 2 - 140 * rpx, myCanvasHeight / 1.58 + b * 16 * rpx, 300);
      }
      context.draw()

      //定时器作用同上
      setTimeout(function () {
        wx.hideLoading();
        that.setData({
          finishh: true,
          fx: false,
          hideModal:true
        })
        //将canvas转换成图片
        wx.canvasToTempFilePath({
          x: 0,
          y: 0,
          canvasId: 'firstCanvas',
          success: function (res) {
            console.log(res)
            var tempArr = []
            tempArr.push(res.tempFilePath)
            that.setData({
              tempFile: tempArr,
            })
            // 保存相册
             wx.saveImageToPhotosAlbum({
               filePath: res.tempFilePath,
                success: function (res) {
                  wx.showToast({
                    title: '分享图片已保存到相册,请到朋友圈选择图片发布'
                  })
                }
              })
          },
          fail: function (res) {
          }
        })
      }, 2000)
    }, 2000)
  },
  preview: function () {
    wx.previewImage({
      urls: this.data.tempFile,
    })
  },
  // 点击去分享朋友圈按钮
  saveFriend:function(){
      var that=this;
      that.setData({
        fx: true,
        shareallType: true,
      })
  },
  // 显示遮罩层 
  showModalTip: function () {
    var that = this;
    that.setData({
      hideModal: false
    })
    var animation = wx.createAnimation({
      duration: 400, //动画的持续时间 默认400ms 数值越大，动画越慢 数值越小，动画越快 
      timingFunction: 'ease', //动画的效果 默认值是linear 
    })
    this.animation = animation
    setTimeout(function () {
      that.fadeIn(); //调用显示动画 
    }, 200)
  },

  // 隐藏遮罩层 
  hideModal: function () {
    var that = this;
    var animation = wx.createAnimation({
      duration: 800, //动画的持续时间 默认400ms 数值越大，动画越慢 数值越小，动画越快 
      timingFunction: 'ease', //动画的效果 默认值是linear 
    })
    this.animation = animation
    that.fadeDown(); //调用隐藏动画 
    setTimeout(function () {
      that.setData({
        hideModal: true
      })
    }, 720) //先执行下滑动画，再隐藏模块 
  },

  //动画集 
  fadeIn: function () {
    this.animation.translateY(0).step()
    this.setData({
      animationData: this.animation.export() //动画实例的export方法导出动画数据传递给组件的animation属性 
    })
  },
  fadeDown: function () {
    this.animation.translateY(300).step()
    this.setData({
      animationData: this.animation.export(),
    })
  },
})