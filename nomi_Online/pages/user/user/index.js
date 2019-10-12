// pages/user/user.js
var util = require('../../../utils/util.js');
var app = getApp();
Page({

    /**ß
     * 页面的初始数据
     */
    data: {
        homeActive: 4,
        user: {},
        showModal: false,
        showService: false,
        showQrcode: false,
    },

    /**
     * 生命周期函数--监听页面加载
     */
    onLoad: function (options) {

        var that = this;
        var uid = wx.getStorageSync('uid');

        // 分享好友
        var myself = options.myself;
        if (myself) {
            wx.redirectTo({
                url: "/pages/login/login?myself=" + myself
            })
        }

        wx.request({

            url: app.data.getUrl + "/user/index",

            method: 'post',

            data: {
                uid: uid,
            },

            header: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },

            success: function (res) {

                if (res.data.status == 1) {

                    var user = res.data.user;
                    var level = res.data.level;

                    that.setData({

                        uid: uid,
                        user: user,
                        level: level,

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
        this.setData({
            showQrcode: false
        })
    },



    /**
     * 购买会员
     */
    buyUser: function(e) {

        var level_type = this.data.user.level;
        var leve_id = e.currentTarget.dataset.type;

        if (level_type > leve_id) {
            wx.showModal({
                title: '提示',
                content: '您已远远超过该等级',
            })
            return false;
        } 
        else if (level_type == leve_id) {
            wx.showModal({
                title: '提示',
                content: '您已达到该等级',
            })
            return false;
        }
        else {

            wx.navigateTo({
                url: "/pages/user/addmine/index?level=" + leve_id + '?order=0'
            })

        }
        

    },



    /**
     * 我的团队
     */
    onMyteam: function (options) {

        // wx.showToast({
        //     title: '功能尚未开放',
        //     icon: "loading",
        //     duration: 800
        // })

        util.isLogin(wx.getStorageSync('uid'));
        if (wx.getStorageSync('uid')) {
            wx.navigateTo({
                url: "/pages/user/team/index"
            })
        }

    },



    /**
     * 我的会员
     */
    onMyuser: function (options) {

        util.isLogin(wx.getStorageSync('uid'));
        if (wx.getStorageSync('uid')) {
            wx.navigateTo({
                url: "/pages/user/member/index"
            })
        }

    },



    /**
     * 每日打卡
     */
    onPunch: function (options) {

        wx.showToast({
            title: '功能尚未开放',
            icon: "loading",
            duration: 800
        })

    },



    /**
     * 客服服务
     */
    onService: function (options) {

        util.isLogin(wx.getStorageSync('uid'));
        if (wx.getStorageSync('uid')) {
            this.setData({
                showService: true
            })
        }
    },

    /**
     * 点击返回按钮隐藏
    */
    back: function () {
        this.setData({
            showService: false
        })
    },

    /**
     * 获取input输入值
    */
    wish_put: function (e) {
        this.setData({
            textV: e.detail.value
        })
    },

    /**
     * 点击确定按钮获取input值并且关闭弹窗
    */
    ok: function (options) {

        var that = this;
        var content = that.data.textV;

        wx.request({

            url: app.data.getUrl + "/offer/index",

            method: 'post',

            data: {
                content: content,
                uid: wx.getStorageSync('uid')
            },

            header: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },

            success: function (res) {

                if (res.data.status == 1) {

                    that.setData({
                        showService: false
                    })

                    wx.showToast({
                        title: '等待客服审核',
                        icon: "success",
                        duration: 1500
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

    },




    /**
     * 个人名片显示
     */
    showQrcode: function () {
        
        util.isLogin(wx.getStorageSync('uid'));
        if (wx.getStorageSync('uid')) {
            this.setData({
                showQrcode: true
            })
        }
        
    },




    /**
     * 分享个人名片
     */
    getQrcode: function(options) {

        let cookie = wx.getStorageSync('cookieKey');//取出Cookie
        let header = { 'Content-Type': 'application/x-www-form-urlencoded' };
        if (cookie) {
            header.Cookie = cookie;
        }

        wx.request({
            url: app.data.getUrl + "/Wxqrcode/qrcodes",
            data: {
                uid: wx.getStorageSync('uid')
            },
            header: header,
            method: 'POST',
            dataType: 'json',
            success: function (res) {
                let qrcodeUrl = res.data.data;//服务器小程序码地址
                console.log(qrcodeUrl);
                wx.navigateTo({
                    url: "/pages/user/qrcode/index"
                })
            },
            fail: function () { },
            complete: options.complete || function () { }
        })

    },




    /** 
     * 分享好友
     */
    onShareAppMessage: function (options) {

        this.setData({
            showQrcode: false
        })

        let that = this;
        //自定义信息
        let sendinfo = {
            userid: wx.getStorageSync('uid')
        }
        let str = JSON.stringify(sendinfo);

        if (options.from == 'button') {
            console.log('按钮分享');
            that.shareReturn(sendinfo.userid);
        }

        return {

            title: '欢迎糯米芽',
            imageUrl: that.data.user.headimg,
            path: '/pages/user/user/index?myself=' + sendinfo.userid,
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
    shareReturn: function (uid) {

        wx.request({

            url: app.data.getUrl + "/user/share",
            method: 'post',
            data: {
                uid: uid
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