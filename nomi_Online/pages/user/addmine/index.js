var app = getApp();
Page({

    /**
     * 页面的初始数据
     */
    data: {

    },

    /**
     * 生命周期函数--监听页面加载
     */
    onLoad: function (options) {

        var that = this;
        var level = options.level;
        var order = options.order;

        wx.request({

            url: app.data.getUrl + "/member/add",

            method: 'post',

            data: {
                level: level,
                order: order,
                uid: wx.getStorageSync('uid')
            },

            header: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },

            success: function (res) {

                if (res.data.status == 1) {

                    that.setData({

                        level: level,
                        list: res.data.list,
                        member: res.data.member,

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



    /* 确认购买 */
    onBuyTap: function (e) {

        var that = this;
        // 购买总价格
        var money = e.currentTarget.dataset.money;
        // var money = '0.01';
        // openid
        var openid = wx.getStorageSync('openid');
        // 购买等级
        var level = that.data.level;

        // wx.showLoading({ title: '正在处理...', });

        wx.request({

            url: app.data.getUrl + "/Memberpay/pay",
            data: {
                money: money,
                openid: openid,
                level: level
            },
            header: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            method: 'POST',
            success: function (res) {

                if (res.data.status == 1) {

                    console.log(res);

                    wx.requestPayment({

                        'nonceStr': res.data.data.nonceStr,
                        'package': res.data.data.package,
                        'paySign': res.data.data.paySign,
                        'timeStamp': res.data.data.timeStamp,
                        'signType': 'MD5',
                        'success': function (res) {

                            // requestPayment:ok
                            // requestPayment:fail
                            if (res.errMsg == 'requestPayment:ok') {

                                wx.showToast({
                                    title: '支付成功',
                                    icon: 'success',
                                    duration: 1500
                                });
                                if (level == 2) {
                                    setTimeout(function () {
                                        wx.redirectTo({
                                            url: "./return/index"
                                        })
                                    }, 1500) //延迟时间 这里是1秒
                                } else {
                                    setTimeout(function () {
                                        wx.redirectTo({
                                            url: "./return/index"
                                        })
                                    }, 1500) //延迟时间 这里是1秒
                                }
                                
                            } else {

                                wx.showToast({
                                    title: '支付失效',
                                    duration: 2000
                                });

                            }

                        },
                        'fail': function (res) {
                            console.log(res);
                        },
                        'complete': function (res) {
                            console.log('complete----' + res);
                        }
                    });

                } else {

                    wx.showToast({
                        title: res.data.msg,
                        duration: 2000
                    });

                }

            },
            fail: function (res) {
                console.log(res)
            }

        });


    },

    
})