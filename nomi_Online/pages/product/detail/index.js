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
                        lunbo: lunbo,
                        region: region,
                        comment: comment,
                        pro: pro,
                        other: other,
                        collected: is_collect,
                        prouid: prouid,
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
    


})