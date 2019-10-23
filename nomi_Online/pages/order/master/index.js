// pages/order/master/index.js
var app = getApp();
var util = require('../../../utils/util.js');
Page({

    /**
     * 页面的初始数据
     */
    data: {
        type: 1,
    },

    /**
     * 生命周期函数--监听页面加载
     */
    onLoad: function (options) {

        var that = this;

        var type = options.type;

        util.isLogin(wx.getStorageSync('uid'));

        wx.request({

            url: app.data.getUrl + "/order/master",

            method: 'post',

            data: {
                type: type,
                uid: wx.getStorageSync('uid')
            },

            header: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },

            success: function (res) {

                if (res.data.status == 1) {

                    var category = res.data.category;
                    var list = res.data.list;
                    var type = res.data.type;
                    
                    that.setData({

                        category: category,
                        list: list,
                        type: type,

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
     * 生命周期函数--监听页面初次渲染完成
     */
    onReady: function () {

    },

    /**
     * 生命周期函数--监听页面显示
     */
    onShow: function () {

    },

    /**
     * 生命周期函数--监听页面隐藏
     */
    onHide: function () {

    },

    /**
     * 生命周期函数--监听页面卸载
     */
    onUnload: function () {

    },

    /**
     * 页面相关事件处理函数--监听用户下拉动作
     */
    onPullDownRefresh: function () {

    },

    /**
     * 页面上拉触底事件的处理函数
     */
    onReachBottom: function () {

    },

    /**
     * 用户点击右上角分享
     */
    onShareAppMessage: function () {

    },




    /**
     * 导航点击
     */
    toCategory: function (e) {

        var that = this;

        var type = e.currentTarget.dataset.id;

        wx.request({

            url: app.data.getUrl + "/order/master",

            method: 'post',

            data: {
                type: type,
                uid: wx.getStorageSync('uid')
            },

            header: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },

            success: function (res) {

                if (res.data.status == 1) {

                    var category = res.data.category;
                    var list = res.data.list;
                    var type = res.data.type;

                    that.setData({

                        category: category,
                        list: list,
                        type: type,

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
    * 取消订单
    */
    onCancel: function (e) {

        wx.showModal({
            title: '提示',
            content: '确定要取消吗？',
            success: function (sm) {
                if (sm.confirm) {
                    
                    var that = this;

                    var orderId = e.currentTarget.dataset.id;
                    var type = e.currentTarget.dataset.type;

                    wx.request({

                        url: app.data.getUrl + "/order/onCancel",

                        method: 'post',

                        data: {
                            orderId: orderId,
                        },

                        header: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },

                        success: function (res) {

                            if (res.data.status == 1) {

                                wx.showToast({
                                    title: res.data.msg,
                                    icon: 'success',//这里打印出登录成功
                                    duration: 1000
                                })
                                wx.redirectTo({
                                    url: "/pages/order/master/index?type"+type
                                })

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

                } else if (sm.cancel) {
                    console.log('用户点击取消');
                    return false;
                }
            }
        })

        

    },




    /**
     * 确认购买
     */
    onBuyTap: function (e) {

        var proid = e.currentTarget.dataset.proid;
        var count = e.currentTarget.dataset.num;
        wx.setStorageSync("now_buy_num", count);
        wx.navigateTo({
            url: "/pages/product/pay/index?proid=" + proid
        })
        

    },




    /**
     * 提醒发货
     */
    onRemind: function () {

        wx.showToast({
            title: '提醒商家成功',
            icon: 'success',
            duration: 1000
        });

    },




    /**
     * 确认收货
     */
    onAffirm: function (e) {

        wx.showModal({
            title: '提示',
            content: '确认收货吗？',
            success: function (sm) {
                if (sm.confirm) {

                    var that = this;

                    var orderId = e.currentTarget.dataset.id;
                    var type = e.currentTarget.dataset.type;

                    wx.request({

                        url: app.data.getUrl + "/order/onAffirm",

                        method: 'post',

                        data: {
                            orderId: orderId,
                        },

                        header: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },

                        success: function (res) {

                            if (res.data.status == 1) {

                                wx.showToast({
                                    title: res.data.msg,
                                    icon: 'success',//这里打印出登录成功
                                    duration: 1000
                                })
                                wx.redirectTo({
                                    url: "/pages/order/master/index?type" + type
                                })

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

                } else if (sm.cancel) {
                    console.log('用户点击取消');
                    return false;
                }
            }
        })

    },




    /**
     * 查看物流页面
     */
    onCheck: function (e) {

        var orderid = e.currentTarget.dataset.id;
        
        wx.redirectTo({
            url: "/pages/order/logistic/index?orderid=" + orderid
        })

    },




    /**
     * 退款
     */
    onQuit: function () {

        wx.showModal({
            title: '提示',
            content: '请与平台联系',
        })

    },





})