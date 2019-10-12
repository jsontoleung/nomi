// pages/product/comment/index.js
var app = getApp();
Page({

    /**
     * 页面的初始数据
     */
    data: {
        types: 0,
    },

    /**
     * 生命周期函数--监听页面加载
     */
    onLoad: function (options) {

        var that = this;
        var pro_id = options.pro_id;
        this.data.currentPostId = pro_id;
        console.log(pro_id);
        wx.request({
            url: app.data.getUrl + "/product/comment",
            method: 'post',
            data: {
                pro_id: pro_id,
                uid: app.data.userId
            },
            header: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            success: function (res) {

                if (res.data.status == 1) {

                    var lists = res.data.lists;
                    var top = res.data.top;
                    that.setData({

                        lists: lists,
                        top: top,

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

    // 头部评价
    toTop: function (e) {

        var that = this;

        var types = e.currentTarget.dataset.id;
        var pro_id = this.data.currentPostId;
        // console.log(types);return false;
        wx.request({

            url: app.data.getUrl + "/product/comment",

            method: "post",

            data: {
                types: types,
                pro_id: pro_id
            },

            header: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },

            success: function (res) {

                if (res.data.status == 1) {

                    var top = res.data.top;
                    var lists = res.data.lists;

                    that.setData({

                        types: types,
                        top: top,
                        lists: lists,

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