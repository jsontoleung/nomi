var app = getApp();

Page({
  data: {
    homeActive: 1,
    pop: false,
  },
  /**
   * 生命周期函数--监听页面加载
   */
    onLoad: function (options) {

        var that = this;

        // 首页转发
        var home = options.home;
        if (home) {
            wx.redirectTo({
                url: "/pages/login/login?home=" + home
            })
        }

        wx.request({

            url: app.data.getUrl + "/index/home",

            method: 'post',

            data: {},

            header: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },

            success: function (res) {

                var lists = res.data.lists;
                var adver = res.data.adver;

                if (res.data.status == 1) {

                that.setData({

                    pop: true,
                    lists: lists,
                    adver: adver

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




    /**
     * 隐藏
     */
    popupHide: function () {
        this.setData({
            pop: false
        })
    },




    /** 
     * 点击图片
     */
    onPop: function () {

        wx.redirectTo({
            url: "/pages/product/index/index"
        })

    },




    // 分享
    onShareAppMessage: function (options) {

        let that = this;
        //自定义信息
        let sendinfo = {
            userid: wx.getStorageSync('uid')
        }
        let str = JSON.stringify(sendinfo);

        that.shareReturn(sendinfo.userid);

        return {

            title: '糯米芽',
            path: '/pages/index/index?home=' + sendinfo.userid,
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