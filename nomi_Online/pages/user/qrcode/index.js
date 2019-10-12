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

            url: app.data.getUrl + "/user/index",

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

                    that.setData({

                        user: user,

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



    //点击开始的时间  
    timestart: function (e) {
        var _this = this;
        _this.setData({ timestart: e.timeStamp });
    },
    //点击结束的时间
    timeend: function (e) {
        var _this = this;
        _this.setData({ timeend: e.timeStamp });
    },

    //保存图片
    saveImg: function (e) {
        var _this = this;
        var times = _this.data.timeend - _this.data.timestart;
        if (times > 300) {
            console.log("长按");
            wx.getSetting({
                success: function (res) {
                    wx.authorize({
                        scope: 'scope.writePhotosAlbum',
                        success: function (res) {
                            console.log("授权成功");
                            var imgUrl = _this.data.user.qrcode;
                            wx.downloadFile({//下载文件资源到本地，客户端直接发起一个 HTTP GET 请求，返回文件的本地临时路径
                                url: imgUrl,
                                success: function (res) {
                                    // 下载成功后再保存到本地
                                    wx.saveImageToPhotosAlbum({
                                        filePath: res.tempFilePath,//返回的临时文件路径，下载后的文件会存储到一个临时文件
                                        success: function (res) {
                                            wx.showToast({
                                                title: '成功保存到相册',
                                                icon: 'success'
                                            })
                                        }
                                    })
                                }
                            })
                        }
                    })
                }
            })
        }
    },



})