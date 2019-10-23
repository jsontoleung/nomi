var app = getApp();
var util = require('../../../utils/util.js');
Page({

    /**
     * 页面的初始数据
     */
    data: {
        homeActive: 3,
        keys: 0,
      background: [],
      menu:'',
      subM:'true'
    },

    /**
     * 生命周期函数--监听页面加载
     */
    onLoad: function (options) {
        var that = this;

        // 糯米臻选转发
        var product = options.product;
        var id = options.id;
        if (product) {
            wx.redirectTo({
                url: "/pages/login/login?product=" + product
            })
        }

        wx.request({

            url: app.data.getUrl + "/product/home",

            method: 'post',

            data: {
                keys: options.keys,
                uid: wx.getStorageSync('uid'),
                id:id
            },

            header: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },

            success: function (res) {
                wx.showToast({
                  title: "正在加载中。。。",
                  icon: 'none',
                  duration: 1500
                });
                var category = res.data.childNav;
                var list = res.data.list;
                var menu = res.data.childNav;
                var background = res.data.banner;
                var category_len = category.length;

                for (var i = 0; i < list.length; i++) {
                  list[i]['price_before'] = parseFloat(list[i]['price_before']);
                  list[i]['price_after'] = parseFloat(list[i]['price_after']);
                  list[i]['vip_price'] = parseFloat(list[i]['vip_price']);
                }

                if (res.data.status == 1) {

                    that.setData({

                        category: category,
                        list: list,
                        menu: menu,
                      background: background,
                      category_len: category_len,
                      ids:id
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


  // 点击轮播图跳转
  bannerHref:function(e){
      var proid = e.currentTarget.dataset.id;
      wx.navigateTo({
        url: "/pages/product/detail/index?pro_id=" + proid
      })
  },
  subBgC:function(e){
    var that=this;
    that.setData({
      subM: true,
    });
  },
  toCategoryMore:function(e){
    var that = this;
    that.setData({
      subM: false,
    });
  },
  // 点击子分类跳转
  menuSub: function (e) {
    var id = e.currentTarget.dataset.id;
    wx.navigateTo({
      url: "/pages/product/sub/index?id=" + id
    })
  },
    /**
     * 点击进入详情
     */
    onClick: function(e) {

        var uid = wx.getStorageSync('uid')
        util.isLogin(uid);
        if (uid) {

            var proid = e.currentTarget.dataset.id;
            var type = e.currentTarget.dataset.type;
            // if (type == false) {
                wx.navigateTo({
                    url: "/pages/product/detail/index?pro_id=" + proid
                })
            // }
            // else {
            //     wx.navigateTo({
            //         url: "/pages/product/serve/index?pro_id=" + proid
            //     })
            // }

        }


    },





    /** 
     * 导航条选择
     */
    toCategory: function (e) {

        var that = this;

        var keys = e.currentTarget.dataset.id;

        var ids = e.currentTarget.dataset.ids;

        wx.request({

          url: app.data.getUrl + "/product/home",

            method: "post",

            data: {
                // keys: keys,
                uid: wx.getStorageSync('uid'),
                id: ids
            },

            header: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },

            success: function (res) {
                wx.showToast({
                  title: "正在加载中。。。",
                  icon: 'none',
                  duration: 1500
                });
                if (res.data.status == 1) {

                    var list = res.data.list;
                    var menu = res.data.childNav;
                    var background = res.data.banner;
                    for (var i = 0; i < list.length; i++) {
                      list[i]['price_before'] = parseFloat(list[i]['price_before']);
                      list[i]['price_after'] = parseFloat(list[i]['price_after']);
                      list[i]['vip_price'] = parseFloat(list[i]['vip_price']);
                    }                      

                    that.setData({

                        keys: keys,
                        list: list,
                        menu: menu,
                        background: background,
                        subM:true,
                        ids: ids
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
     * 右上角转发
     */
    onShareAppMessage: function (options) {

        let that = this;
        //自定义信息
        let sendinfo = {
            userid: wx.getStorageSync('uid')
        }
        let str = JSON.stringify(sendinfo);

        that.shareReturn(sendinfo.userid);

        return {

            title: '糯米臻选',
            path: '/pages/product/index/index?product=' + sendinfo.userid,
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