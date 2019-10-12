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

            url: app.data.getUrl + "/Myintegral/index",

            method: 'post',

            data: {
                uid: wx.getStorageSync('uid')
            },

            header: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },

            success: function (res) {

                if (res.data.status == 1) {

                    var total = res.data.total;
                    var record = res.data.record;

                    that.setData({

                        total: total,
                        record: record,

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
     * 生命周期函数--监听页面显示
     */
    onShow: function () {
        wx.showToast({
            title: '加载中',
            icon: "loading",
            duration: 1000
        })
    },


    /**
     * 点击提现按钮
     */
    onExtract: function (e) {
        
        var total = this.data.total.balance;

        wx.navigateTo({
            url: "/pages/user/extract/index?total=" + total
        })

        // wx.showToast({
        //     title: '9月1号开放',
        //     icon: "loading",
        //     duration: 800
        // })

    },

})