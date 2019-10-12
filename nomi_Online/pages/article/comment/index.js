// pages/article/comment/index.js
var app = getApp();
Page({

    /**
     * 页面的初始数据
     */
    data: {
        nickname: '',
        juniorid: 0,
    },

    /**
     * 生命周期函数--监听页面加载
     */
    onLoad: function (options) {

        var that = this;
        var comid = options.comid;
        this.data.currentPostId = comid;
        wx.request({
            url: app.data.getUrl + "/article/junior",
            method: 'post',
            data: {
                comid: comid,
                uid: wx.getStorageSync('uid')
            },
            header: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            success: function (res) {

                if (res.data.status == 1) {

                    var junior = res.data.junior;
                    var juniorCount = res.data.juniorCount;
                    
                    that.setData({

                        junior: junior,
                        juniorCount: juniorCount,

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


    // 下级评论点赞
    _juniorLike: function (e) {

        var that = this;
        var id = e.currentTarget.dataset.id;
        var artid = this.data.currentPostId;
        var like = e.currentTarget.dataset.like;
       

        wx.request({
            url: app.data.getUrl + "/article/juniorLike",
            method: 'post',
            data: {
                id: id,
                artid: artid,
                like: like,
                uid: wx.getStorageSync('uid')
            },
            header: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            success: function (res) {
                if (res.data.status == 1) {

                    that.setData({
                        junior: res.data.junior
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



    // 点击头像
    onClickTap:function (e) {

        var comid = e.currentTarget.dataset.comid;
        var name = '@' + e.currentTarget.dataset.name + ' ';
        this.setData({
            juniorid: comid,
            nickname: name,
        })

    },



    /**
    * 发表评论
    */
    formSubmit: function (e) {

        var that = this;
        var juniorid = e.detail.value.juniorid;
        var content = e.detail.value.content;
        var comid = this.data.currentPostId;
        
        if (juniorid == '') {
            wx.showToast({
                title: '@不到',
                duration: 2000
            })
        } else {

            wx.request({

                url: app.data.getUrl + "/article/reply",

                method: "post",

                data: {
                    comid: comid,
                    juniorid: juniorid,
                    content: content,
                    uid: wx.getStorageSync('uid')

                },

                header: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },

                success: function (res) {

                    if (res.data.status == 1) {

                        that.setData({

                            junior: res.data.junior,
                            juniorCount: res.data.juniorCount,

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

        }

    },

})