// pages/user/member/index.js
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

        wx.request({

            url: app.data.getUrl + "/member/home",

            method: 'post',

            data: {
                uid: wx.getStorageSync('uid')
            },

            header: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },

            success: function (res) {

                if (res.data.status == 1) {

                    var user = res.data.user;
                    var userPrivilege = res.data.userPrivilege;
                    var nextPrivilege = res.data.nextPrivilege;
                    var nextUser = res.data.nextUser;

                    that.setData({

                        user: user,
                        userPrivilege: userPrivilege,
                        nextPrivilege: nextPrivilege,
                        nextUser: nextUser,

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
     * 购买会员
     */
    buyUser: function (e) {

        var that = this;
        var leve_id = that.data.user.nextLevel;

        wx.navigateTo({
            url: "/pages/user/addmine/index?level=" + leve_id + '?order=1'
        })


    },

    
})