// pages/user/study/index.js
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

            url: app.data.getUrl + "/user/study",

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

                    var category = res.data.category;
                    var lists = res.data.lists;

                    that.setData({

                        keys: keys,
                        category: category,
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


    /** 选择栏目 */
    toCategory: function (options) {

        var that = this;
        var keys = options.currentTarget.dataset.id;
        console.log(keys);
        wx.request({

            url: app.data.getUrl + "/user/study",

            method: "post",

            data: {
                keys: keys,
                uid: wx.getStorageSync('uid')
            },

            header: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },

            success: function (res) {

                if (res.data.status == 1) {

                    var category = res.data.category;
                    var lists = res.data.lists;

                    that.setData({

                        keys: keys,
                        category: category,
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