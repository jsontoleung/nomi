// pages/product/pay/index.js
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
        var proid = options.proid;

        this.data.currentPostId = proid;

        wx.request({
            url: app.data.getUrl + "/payment/home",
            method: 'post',
            data: {
                proid: proid,
                uid: wx.getStorageSync('uid')
            },
            header: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            success: function (res) {

                var addr = res.data.addr;
                var proInfo = res.data.proInfo;

                that.setData({
                    buy_num: res.data.buy_num,
                    addr: addr,
                    proInfo: proInfo,
                    count: proInfo.price_after,
                });

            },
            fail: function (e) {
                wx.showToast({
                    title: '网络异常',
                    duration: 2000
                });
            },
        })

    },


    /* 点击减号 */
    bindMinus: function (e) {

        var buy_num = this.data.buy_num;
        var count = e.currentTarget.dataset.count;

        // 如果大于1时，才可以减  
        if (buy_num > 1) {
            buy_num--;
            count = Math.floor(count * 100) / 100 - Math.floor(this.data.proInfo.price_after * 100) / 100;
        }

        // 将数值与状态写回  
        this.setData({
            buy_num: buy_num,
            count: count,
        });

    },

    /* 点击加号 */
    bindPlus: function (e) {

        var buy_num = this.data.buy_num;
        var count = e.currentTarget.dataset.count;

        // 不作过多考虑自增1
        if (buy_num >= this.data.proInfo.inventory) {
            wx.showToast({
                title: '库存有限喔!',
                duration: 1000
            })
        } else {
            buy_num++;
            count = Math.floor(count * 100) / 100 + Math.floor(this.data.proInfo.price_after * 100) / 100;
        }

        // 将数值与状态写回  
        this.setData({
            buy_num: buy_num,
            count: count
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


    /* 确认购买 */
    onBuyTap: function (e) {
        
        var that = this;

        if (that.data.addr == null || that.data.addr == 'undifind' || that.data.addr == '') {
            wx.showModal({
                title: '警告',
                content: '请填写地址',
                confirmText: '知道了',
                showCancel: false,
                success(res) {
                    if (res.confirm) {
                        return false;
                    } else if (res.cancel) {
                        return false;
                    }
                }
            })
        }

        // 购买商品数量
        var buyNum = e.currentTarget.dataset.buynum;
        // 购买总价格
        var count = e.currentTarget.dataset.count;
        // 产品id
        var proid = this.data.currentPostId;
        // openid
        var openid = wx.getStorageSync('openid');
        
        wx.request({

            url: app.data.getUrl + "/pay/pay",
            data: {
                proid: proid,
                buyNum: buyNum,
                count: count,
                openid: openid
            },
            header: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            method: 'POST',
            success: function (res) {

                if (res.data.status == 1) {

                    var order_sn = res.data.order_sn;

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
                                    duration: 2000
                                });
                                setTimeout(function () {
                                    wx.navigateTo({
                                        url: "/pages/product/notify/index?order_sn=" + order_sn
                                    })
                                }, 2000) //延迟时间 这里是1秒

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