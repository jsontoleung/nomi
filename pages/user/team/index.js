var app = getApp();
Page({

    /**
     * 页面的初始数据
     */
    data: {
        keys: 0,
    },

    /**
     * 生命周期函数--监听页面加载
     */
    onLoad: function (options) {

        var that = this;

        var keys = options.keys;

        wx.request({

            url: app.data.getUrl + "/Myteam/index",

            method: 'post',

            data: {
                keys: keys,
                uid: wx.getStorageSync('uid')
            },

            header: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },

            success: function (res) {

                if (res.data.status == 1) {

                    var user = res.data.user;
                    var junior = res.data.junior;

                    that.setData({

                        user: user,
                        junior: junior,

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
     * title互换
     */
    onListInfo: function (e) {

        var that = this;

        var keys = e.currentTarget.dataset.type;

        wx.request({

            url: app.data.getUrl + "/Myteam/index",

            method: 'post',

            data: {
                keys: keys,
                uid: wx.getStorageSync('uid')
            },

            header: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },

            success: function (res) {

                if (res.data.status == 1) {

                    var user = res.data.user;
                    var junior = res.data.junior;

                    that.setData({

                        keys: keys,
                        user: user,
                        junior: junior,

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





})