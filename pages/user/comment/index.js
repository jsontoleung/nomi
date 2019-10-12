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

            url: app.data.getUrl + "/user/comment",

            method: 'post',

            data: {
                uid: wx.getStorageSync('uid')
            },

            header: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },

            success: function (res) {

                if (res.data.status == 1) {

                    var list = res.data.list;

                    that.setData({

                        list: list,

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