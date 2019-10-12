// pages/user/addmine/return/index.js
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

        wx.showToast({
            title: '请领取产品!',
            icon: 'none',
            duration: 2000
        })

        wx.request({

            url: app.data.getUrl + "/member/memberRetrun",

            method: 'post',

            data: {
                uid: wx.getStorageSync('uid')
            },

            header: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },

            success: function (res) {

                if (res.data.status == 1) {

                    that.setData({

                        user: res.data.user,
                        pro: res.data.pro,
                        userPrivilege: res.data.userPrivilege,

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

    // 点击领取商品
    ongetTap: function (e) {

        // 产品id
        var proid = e.currentTarget.dataset.proid;

        wx.request({

            url: app.data.getUrl + "/member/giveOrder",

            method: 'post',

            data: {
                proid: proid,
                uid: wx.getStorageSync('uid')
            },

            header: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },

            success: function (res) {

                if (res.data.status == 1) {

                    wx.showToast({
                        title: '领取成功,等待平台发货',
                        icon: 'SUCCESS',
                        duration: 2000
                    })
                    setTimeout(function () {
                        wx.redirectTo({
                            url: "/pages/user/user/index"
	                    })
                    }, 1000) //延迟时间 这里是2秒

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