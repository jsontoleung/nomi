// pages/index/more/more.js
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
        var cid = options.cid;
        console.log(cid);
        wx.request({

            url: app.data.getUrl + "/index/more",

            method: 'post',

            data: {
                cid: cid,
            },

            header: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },

            success: function (res) {

                var lists = res.data.lists;

                if (res.data.status == 1) {

                    that.setData({

                        lists: lists

                    });

                } else {

                    wx.showToast({

                        title: "错误异常",

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