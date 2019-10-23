//app.js
App({
    data: {
        //getUrl: "http://www.nomitest.com/index.php/api",//本地
        getUrl: "https://www.nomiyy.com/index.php/api",
        //   getUrl: "http://www.nomi.cn/index.php/api/",
        userId: null, 
    },

    onLaunch: function () {

        // 展示本地存储能力
        var logs = wx.getStorageSync('logs') || []
        logs.unshift(Date.now())
        wx.setStorageSync('logs', logs)
        // 获取用户信息
        wx.getSetting({
            success: res => {
                this.getUserInfo();
                if (res.authSetting['scope.userInfo']) {
                    // 已经授权，可以直接调用 getUserInfo 获取头像昵称，不会弹框
                    wx.getUserInfo({
                        success: res => {
                            // 可以将 res 发送给后台解码出 unionId
                            this.globalData.userInfo = res.userInfo
                            // 由于 getUserInfo 是网络请求，可能会在 Page.onLoad 之后才返回
                            // 所以此处加入 callback 以防止这种情况
                            if (this.userInfoReadyCallback) {
                                this.userInfoReadyCallback(res)
                            }
                        }
                    })
                }
            }
        })

    },


    getUserInfo: function (cb) {
        var that = this
        if (this.globalData.userInfo) {
            typeof cb == "function" && cb(this.globalData.userInfo)
        } else {
            //调用登录接口
            wx.login({
                success: function (res) {
                    var code = res.code;
                    that.data.code = code;
                    //get wx user simple info
                    wx.getUserInfo({
                        success: function (res) {
                            that.globalData.userInfo = res.userInfo;
                            var encryptedData = encodeURIComponent(res.encryptedData);
                            var iv = res.iv;
                            typeof cb == "function" && cb(that.globalData.userInfo);
                            that.getUserSessionKey(code, encryptedData, iv);
                        }
                    });
                }
            });
        }
    },
    getUserSessionKey: function (code, encryptedData, iv) {

        var that = this;
        wx.request({
            url: that.data.getUrl + '/login/getsessionkey',
            method: 'post',
            data: {
                code: code
            },
            header: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            success: function (res) {

                if (res && res.header && res.header['Set-Cookie']) {
                    wx.setStorageSync('cookieKey', res.header['Set-Cookie']);
                }

                var data = res.data;

                if (data.status == 0) {
                    wx.showToast({
                        title: data.msg,
                        duration: 2000
                    });
                    return false;
                }
                that.globalData.userInfo['sessionId'] = data.res.session_key;
                that.globalData.userInfo['openid'] = data.res.openid;
                that.globalData.userInfo['encryptedData'] = encryptedData;
                that.globalData.userInfo['iv'] = iv;

                that.onLoginUser();
            },
            fail: function (e) {
                wx.showToast({
                    title: '网络异常！',
                    duration: 2000
                });
            },
        });
    },

    onLoginUser: function () {

        var that = this;
        var user = that.globalData.userInfo;

        var topuid = '';
        var channel_id='';

        // 是否渠道进来
        var channel_id = wx.getStorageSync('channel_ids');
        if (channel_id) {
          var channel_id = channel_id;
        }
      console.log(channel_id+'sd')

        // 文章分享
        var artuid = wx.getStorageSync('artuid');
        var artid = wx.getStorageSync('artid');
        if (artuid && artid) {
            var topuid = artuid;
        }

        // 产品分享
        var prouid = wx.getStorageSync('prouid');
        var pro_id = wx.getStorageSync('pro_id');
        if (prouid && pro_id) {
            var topuid = prouid;
        }

        // 名片分享
        var shareid = wx.getStorageSync('shareid');
        if (shareid) {
            var topuid = shareid;
        }

        // 分享好友
        var myself = wx.getStorageSync('myself');
        if (myself) {
            var topuid = myself;
        }

        // 首页转发
        var home = wx.getStorageSync('home');
        if (home) {
            var topuid = home;
        }

        // 糯米臻选转发
        var product = wx.getStorageSync('product');
        if (product) {
            var topuid = product;
        }
      console.log(topuid+'dddd')

        wx.request({

            url: that.data.getUrl + "/login/wxlogin",

            method: 'post',

            data: {
                headimg: user.avatarUrl,

                session_key: user.sessionId,

                openid: user.openid,

                encrypteData: user.encryptedData,

                iv: user.iv,
                
                topuid: topuid,

                channel_id: channel_id
            },
            header: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            success: function (res) {
              console.log(res)
                var status = res.data.status;

                var users = res.data.user;
                
                if (status != 1) {

                    wx.showToast({
                        title: String(res.data.msg),
                        duration: 3000

                    });
                    return false;
                }

                that.globalData.userInfo['user_id'] = users.user_id;

                that.globalData.userInfo['avatar'] = users.headimg;

                var userId = users.user_id;

                if (!userId) {
                    wx.showToast({
                        title: '登录失败！',
                        duration: 3000
                    });
                    return false;
                }

                that.data.userId = userId;
                wx.setStorageSync('uid', userId);
                wx.setStorageSync('openid', that.globalData.userInfo['openid']);
                wx.setStorageSync('loginType', users.type);
            },

            fail: function () {
                wx.showToast({
                    title: '网络错误！',
                    duration: 2000
                })
            },
        })
    },
    globalData: {
        userInfo: null
    },



    onShow: function (options) {

        var that = this;

        if (options.scene == 1007) {
            
            console.log('通过单人聊天会话分享进入');
        }
        if (options.scene == 1008) {
            console.log('通过群聊会话分享进入');
            
        }
        if (options.scene == 1001) {
            console.log('通过发现栏小程序进入');
        }

    },








})