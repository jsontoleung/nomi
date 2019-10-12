var app = getApp();
Page({
  /**
   * 页面的初始数据
   */
  data: {
    userInfo: {},
    canIUse: wx.canIUse('button.open-type.getUserInfo'),
  },

    /**
     * 生命周期函数--监听页面加载
     */
    onLoad: function (options) {
      
        var that = this;
        wx.showToast({
            title: '加载中',   	//设置标题
            duration: 1000, 	 //设置显示时间
            mask: true,			//是否打开遮罩,默认不打开
            icon: 'loading'			//图标样式，none为无图标
        });

        // 文章分享
        var artuid = options.artuid;
        var artid = options.artid;
        if (artuid && artid) {
            wx.setStorageSync('artuid', artuid);
            wx.setStorageSync('artid', artid);
        }


        // 产品分享
        var prouid = options.prouid;
        var pro_id = options.pro_id;
        if (prouid && pro_id) {
            wx.setStorageSync('prouid', prouid);
            wx.setStorageSync('pro_id', pro_id);
        }


        // 名片分享
        var shareid = options.shareid;
        if (shareid) {
            wx.setStorageSync('shareid', shareid);
        }

        // 分享好友
        var myself = options.myself;
        if (myself) {
            wx.setStorageSync('myself', myself);
        }

        // 首页转发
        var home = options.home;
        if (home) {
            wx.setStorageSync('home', home);
        }

        // 糯米臻选转发
        var product = options.product;
        if (product) {
            wx.setStorageSync('product', product);
        }
        

        // 查看是否授权
        wx.getSetting({
            success: function (res) {

                if (res.authSetting['scope.userInfo']) {
                // 查看是否授权
                app.getUserInfo(function (userInfo) {

                    that.setData({
                        userInfo: userInfo,
                    })

                    if (artuid && artid) {
                        wx.navigateTo({
                            url: '/pages/article/detail/index?id=' + artid
                        })
                    }

                    if (prouid && pro_id) {
                        wx.navigateTo({
                            url: '/pages/product/detail/index?pro_id=' + pro_id
                        })
                    }
                    
                    if (shareid) {
                        wx.redirectTo({
                            url: "/pages/index/index"
                        })
                    }

                    if (myself) {
                        wx.redirectTo({
                            url: '/pages/user/user/index'
                        })
                    }

                    if (home) {
                        wx.redirectTo({
                            url: '/pages/index/index'
                        })
                    }

                    if (product) {
                        wx.redirectTo({
                            url: '/pages/product/index/index'
                        })
                    }
                   
                    wx.redirectTo({
                        url: "/pages/index/index"
                    })

                });
                } else {
                    console.log('用户没有进行授权！' + res.errMsg)
                }
            }
        })
    },
  
    bindGetUserInfo: function (e) {

        if (e.detail.userInfo) {

            var that = this;

            // 文章分享
            var artuid = wx.getStorageSync('artuid');
            var artid = wx.getStorageSync('artuid');

            // 产品分享
            var prouid = wx.getStorageSync('prouid');
            var pro_id = wx.getStorageSync('pro_id');

            // 名片分享
            var shareid = wx.getStorageSync('shareid');

            // 分享好友
            var myself = wx.getStorageSync('myself');

            // 首页转发
            var home = wx.getStorageSync('home');

            // 糯米臻选转发
            var product = wx.getStorageSync('product');
            

            app.getUserInfo(function (userInfo) {

                that.setData({
                    userInfo: userInfo,
                })

                if (artuid && artid) {
                    wx.navigateTo({
                        url: '/pages/article/detail/index?id=' + artid
                    })
                }
                if (prouid && pro_id) {
                    wx.navigateTo({
                        url: '/pages/product/detail/index?pro_id=' + pro_id
                    })
                }
                if (shareid) {
                    wx.redirectTo({
                        url: "/pages/index/index"
                    })
                }
                if (myself) {
                    wx.redirectTo({
                        url: '/pages/user/user/index?myself=' + myself
                    })
                }

                if (home) {
                    wx.redirectTo({
                        url: '/pages/index/index'
                    })
                }

                if (product) {
                    wx.redirectTo({
                        url: '/pages/product/index/index'
                    })
                }

                wx.redirectTo({
                    url: "/pages/index/index"
                })

            });
            
        } else {

            //用户按了拒绝按钮
            wx.showModal({
                title: '警告',
                content: '您点击了拒绝授权，将无法进入小程序，请授权之后再进入!!!',
                showCancel: false,
                confirmText: '返回授权',
                success: function (res) {
                    // 用户没有授权成功，不需要改变 isHide 的值
                    if (res.confirm) {
                        console.log('用户点击了“返回授权”');
                    }
                }
            });

        }
    },


})